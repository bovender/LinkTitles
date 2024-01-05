<?php

/**
 * The LinkTitles\Linker class does the heavy linking for the extension.
 *
 * Copyright 2012-2024 Daniel Kraus <bovender@bovender.de> ('bovender')
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @author Daniel Kraus <bovender@bovender.de>
 */
namespace LinkTitles;

/**
 * Performs the actual linking of content to existing pages.
 */
class Linker {
	/**
	 * LinkTitles configuration.
	 *
	 * @var Config $config
	 */
	public $config;

	/**
	 * The link value of the target page that is currently being evaluated.
	 * This may be either the page name or the page name prefixed with the
	 * name space if the target's name space is not NS_MAIN.
	 *
	 * This is an instance variable (rather than a local method variable) so it
	 * can be accessed in the preg_replace_callback callbacks.
	 *
	 * @var String $linkValue
	 */
	private $linkValue;

	private static $locked = 0;

	/**
	 * Constructs a new instance of the Linker class.
	 *
	 * @param Config $config LinkTitles configuration object.
	 */
	public function __construct( Config &$config ) {
		$this->config = $config;
	}

	/**
	 * Core function of the extension, performs the actual parsing of the content.
	 *
	 * This method receives a Title object and the string representation of the
	 * source page. It does not work on a WikiPage object directly because the
	 * callbacks in the Extension class do not always get a WikiPage object in the
	 * first place.
	 *
	 * @param \Title &$title Title object for the current page.
	 * @param String $text String that holds the article content
	 * @return String|null Source page text with links to target pages, or null if no links were added
	 */
	public function linkContent( Source $source ) {
		if ( self::$locked > 0 || !$source->canBeLinked() ) {
			return;
		}

		( $this->config->firstOnly ) ? $limit = 1 : $limit = -1;
		$limitReached = false;
		$newLinks = false; // whether or not new links were added
		$newText = $source->getText();
		$splitter = Splitter::singleton( $this->config );
		$targets = Targets::singleton( $source->getTitle(), $this->config );

		// Iterate through the target page titles
		foreach( $targets->queryResult as $row ) {
			$target = new Target( $row->page_namespace, $row->page_title, $this->config );

			// Don't link current page and don't link if the target page redirects
			// to the current page or has the __NOAUTOLINKTARGET__ magic word
			// (as required by the actual LinkTitles configuration).
			if ( $target->isSameTitle( $source ) || !$target->mayLinkTo( $source ) ) {
				continue;
			}

			// Dealing with existing links if the firstOnly option is set:
			// A link to the current page should only be recognized if it appears in
			// clear text, i.e. we do not count piped links as existing links.
			// (Similarly, by design, redirections should not be counted as existing links.)
			if ( $limit == 1 && preg_match( '/\[\[' . $target->getCaseSensitiveLinkValueRegex() . ']]/' , $source->getText() ) ) {
				continue;
			}

			// Split the page content by non-linkable sections.
			// Credits to inhan @ StackOverflow for suggesting preg_split.
			// See http://stackoverflow.com/questions/10672286
			$arr = $splitter->split( $newText );
			$count = 0;

			// Cache the target title text for the regex callbacks
			$this->linkValue = $target->getPrefixedTitleText();

			// Even indexes will point to sections of the text that may be linked
			for ( $i = 0; $i < count( $arr ); $i += 2 ) {
				$arr[$i] = preg_replace_callback( $target->getCaseSensitiveRegex(),
					array( $this, 'simpleModeCallback'),
					$arr[$i], $limit, $replacements );
				$count += $replacements;
				if ( $this->config->firstOnly && ( $count > 0 ) ) {
					$limitReached = true;
					break;
				};
			};
			if ( $count > 0 ) {
				$newLinks = true;
				$newText = implode( '', $arr );
			}

			// If smart mode is turned on, the extension will perform a second
			// pass on the page and add links with aliases where the case does
			// not match.
			if ( $this->config->smartMode && !$limitReached ) {
				if ( $count > 0 ) {
					// Split the text again because it was changed in the first pass.
					$arr = $splitter->split( $newText );
				}

				for ( $i = 0; $i < count( $arr ); $i+=2 ) {
					// even indexes will point to text that is not enclosed by brackets
					$arr[$i] = preg_replace_callback( $target->getCaseInsensitiveRegex(),
						array( $this, 'smartModeCallback'),
						$arr[$i], $limit, $replacements );
					$count += $replacements;
					if ( $this->config->firstOnly && ( $count > 0  )) {
						break;
					};
				};
				if ( $count > 0 ) {
					$newLinks = true;
					$newText = implode( '', $arr );
				}
			} // $wgLinkTitlesSmartMode
		}; // foreach $res as $row

		if ( $newLinks ) {
			return $newText;
		}
	}

	/**
	 * Callback for preg_replace_callback in simple mode.
	 *
	 * @param array $matches Matches provided by preg_replace_callback
	 * @return string Target page title with or without link markup
	 */
	private function simpleModeCallback( array $matches ) {
		// If the link value is longer than the match, it must be prefixed with
		// a namespace. In this case, we build a piped link.
		if ( strlen( $this->linkValue ) > strlen( $matches[0] ) ) {
			return '[[' . $this->linkValue . '|' . $matches[0] . ']]';
		} else {
			return '[[' . $matches[0] . ']]';
		}
	}

	/**
	 * Callback function for use with preg_replace_callback.
	 * This essentially performs a case-sensitive comparison of the
	 * current page title and the occurrence found on the page; if
	 * the cases do not match, it builds an aliased (piped) link.
	 * If $wgCapitalLinks is set to true, the case of the first
	 * letter is ignored by MediaWiki and we don't need to build a
	 * piped link if only the case of the first letter is different.
	 *
	 * @param array $matches Matches provided by preg_replace_callback
	 * @return string Target page title with or without link markup
	 */
	private function smartModeCallback( array $matches ) {
		// If cases of the target page title and the actual occurrence in the text
		// are not identical, we need to build a piped link.
		// How case-identity is determined depends on the $wgCapitalLinks setting:
		// with $wgCapitalLinks = true, the case of first letter of the title is
		// not significant.
		if ( $this->config->capitalLinks ) {
			$needPipe = strcmp( substr( $this->linkValue, 1 ), substr( $matches[ 0 ], 1 ) ) != 0;
		} else {
			$needPipe = strcmp( $this->linkValue, $matches[ 0 ] ) != 0;
		}
		if ( $needPipe ) {
			return '[[' . $this->linkValue . '|' . $matches[ 0 ] . ']]';
		} else  {
			return '[[' . $matches[ 0 ]  . ']]';
		}
	}

	/**
	 * Increases an internal static lock counter by 1.
	 *
	 * If the Linker class is locked (counter > 0), linkContent() will be a no-op.
	 * Locking is necessary to enable nested <noautolinks> and <autolinks> tags in
	 * parseOnRender mode.
	 */
	public static function lock() {
		self::$locked += 1;
	}

	/**
	 * Decreases an internal static lock counter by 1.
	 *
	 * If the Linker class is locked (counter > 0), linkContent() will be a no-op.
	 * Locking is necessary to enable nested <noautolinks> and <autolinks> tags in
	 * parseOnRender mode.
	 */
	public static function unlock() {
		self::$locked -= 1;
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
