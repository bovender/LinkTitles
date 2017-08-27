<?php
/**
 * The LinkTitles\Linker class does the heavy linking for the extension.
 *
 * Copyright 2012-2017 Daniel Kraus <bovender@bovender.de> ('bovender')
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
	/// A Title object for the page that is being parsed.
	private $currentTitle;

	/// A Title object for the target page currently being examined.
	private $targetTitle;

	// The TitleValue object of the target page
	private $targetTitleValue;

	/// The content object for the currently processed target page.
	/// This variable is necessary to be able to prevent loading the target
	/// content twice.
	private $targetContent;

	/// Holds the page title of the currently processed target page
	/// as a string.
	private $targetTitleText;

	/**
	 * LinkTitles configuration.
	 *
	 * @var Config $config
	 */
	public $config;

	/**
	 * Constructs a new instance of the Linker class.
	 *
	 * @param Config $config LinkTitles configuration object.
	 */
	public function __construct( Config &$config ) {
		$this->config = $config;
	}

	/*
	 * Core function of the extension, performs the actual parsing of the content.
	 *
	 * @param Parser $parser Parser instance for the current page
	 * @param String $text String that holds the article content
	 * @returns String String with links to target pages
	 */
	public function linkContent( \Title &$title, &$text ) {

		( $this->config->firstOnly ) ? $limit = 1 : $limit = -1;
		$limitReached = false;
		$this->currentTitle = $title;
		$newText = $text;

		$delimiters = Delimiters::default( $this->config );
		$targets = Targets::default( $title, $this->config );

		// Iterate through the page titles
		foreach( $targets->queryResult as $row ) {
			$this->newTarget( $row->page_namespace, $row->page_title );

			// Don't link current page
			if ( $this->targetTitle->equals( $this->currentTitle ) ) { continue; }

			// split the page content by [[...]] groups
			// credits to inhan @ StackOverflow for suggesting preg_split
			// see http://stackoverflow.com/questions/10672286
			$arr = preg_split( $delimiters->splitter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

			// Escape certain special characters in the page title to prevent
			// regexp compilation errors
			$this->targetTitleText = $this->targetTitle->getText();
			$quotedTitle = preg_quote( $this->targetTitleText, '/' );

			$this->ltDebugLog( 'TargetTitle='. $this->targetTitleText, 'private' );
			$this->ltDebugLog( 'TargetTitleQuoted='. $quotedTitle, 'private' );

			// Depending on the global configuration setting $wgCapitalLinks,
			// the title has to be searched for either in a strictly case-sensitive
			// way, or in a 'fuzzy' way where the first letter of the title may
			// be either case.
			if ( $this->config->capitalLinks && ( $quotedTitle[0] != '\\' )) {
				$searchTerm = '((?i)' . $quotedTitle[0] . '(?-i)' .
					substr($quotedTitle, 1) . ')';
			}	else {
				$searchTerm = '(' . $quotedTitle . ')';
			}

			$regex = '/(?<![\:\.\@\/\?\&])' . $delimiters->wordStart . $searchTerm . $delimiters->wordEnd . '/S';
			for ( $i = 0; $i < count( $arr ); $i+=2 ) {
				// even indexes will point to text that is not enclosed by brackets
				$arr[$i] = preg_replace_callback( $regex,
					array( $this, 'simpleModeCallback'),
					$arr[$i], $limit, $count );
				if ( $this->config->firstOnly && ( $count > 0 ) ) {
					$limitReached = true;
					break;
				};
			};
			$newText = implode( '', $arr );

			// If smart mode is turned on, the extension will perform a second
			// pass on the page and add links with aliases where the case does
			// not match.
			if ( $this->config->smartMode && !$limitReached ) {
				$arr = preg_split( $delimiters->splitter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

				for ( $i = 0; $i < count( $arr ); $i+=2 ) {
					// even indexes will point to text that is not enclosed by brackets
					$arr[$i] = preg_replace_callback( '/(?<![\:\.\@\/\?\&])' .
						$delimiters->wordStart . '(' . $quotedTitle . ')' .
						$delimiters->wordEnd . '/iS',
						array( $this, 'smartModeCallback'),
						$arr[$i], $limit, $count );
					if ( $this->config->firstOnly && ( $count > 0  )) {
						break;
					};
				};
				$newText = implode( '', $arr );
			} // $wgLinkTitlesSmartMode
		}; // foreach $res as $row
		return $newText;
	}

	// Build an anonymous callback function to be used in simple mode.
	private function simpleModeCallback( array $matches ) {
		if ( $this->checkTargetPage() ) {
			$this->ltLog( "Linking '$matches[0]' to '" . $this->targetTitle . "'" );
			return '[[' . $matches[0] . ']]';
		}
		else
		{
			return $matches[0];
		}
	}

	// Callback function for use with preg_replace_callback.
	// This essentially performs a case-sensitive comparison of the
	// current page title and the occurrence found on the page; if
	// the cases do not match, it builds an aliased (piped) link.
	// If $wgCapitalLinks is set to true, the case of the first
	// letter is ignored by MediaWiki and we don't need to build a
	// piped link if only the case of the first letter is different.
	private function smartModeCallback( array $matches ) {

		if ( $this->config->capitalLinks ) {
			// With $wgCapitalLinks set to true we have a slightly more
			// complicated version of the callback than if it were false;
			// we need to ignore the first letter of the page titles, as
			// it does not matter for linking.
			if ( $this->checkTargetPage() ) {
				$this->ltLog( "Linking (smart) '$matches[0]' to '" . $this->targetTitle . "'" );
				if ( strcmp(substr($this->targetTitleText, 1), substr($matches[0], 1)) == 0 ) {
					// Case-sensitive match: no need to bulid piped link.
					return '[[' . $matches[0] . ']]';
				} else  {
					// Case-insensitive match: build piped link.
					return '[[' . $this->targetTitleText . '|' . $matches[0] . ']]';
				}
			}
			else
			{
				return $matches[0];
			}
		} else {
			// If $wgCapitalLinks is false, we can use the simple variant
			// of the callback function.
			if ( $this->checkTargetPage() ) {
				$this->ltLog( "Linking (smart) '$matches[0]' to '" . $this->targetTitle . "'" );
				if ( strcmp($this->targetTitleText, $matches[0]) == 0 ) {
					// Case-sensitive match: no need to bulid piped link.
					return '[[' . $matches[0] . ']]';
				} else  {
					// Case-insensitive match: build piped link.
					return '[[' . $this->targetTitleText . '|' . $matches[0] . ']]';
				}
			}
			else
			{
				return $matches[0];
			}
		}
	}

	/// Sets member variables for the current target page.
	private function newTarget( $ns, $title ) {
		$this->targetTitle = \Title::makeTitleSafe( $ns, $title );
		$this->ltDebugLog( 'newtarget='.  $this->targetTitle->getText(), "private" );
		$this->targetTitleValue = $this->targetTitle->getTitleValue();
		$this->ltDebugLog( 'altTarget='. $this->targetTitleValue->getText(), "private" );
		$this->targetContent = null;
	}

	/// Returns the content of the current target page.
	/// This function serves to be used in preg_replace_callback callback
	/// functions, in order to load the target page content from the
	/// database only when needed.
	/// @note It is absolutely necessary that the newTarget()
	/// function is called for every new page.
	private function getTargetContent() {
		if ( ! isset( $targetContent ) ) {
			$this->targetContent = \WikiPage::factory( $this->targetTitle )->getContent();
		};
		return $this->targetContent;
	}

	/// Examines the current target page. Returns true if it may be linked;
	/// false if not. This depends on the settings
	/// $wgLinkTitlesCheckRedirect and $wgLinkTitlesEnableNoTargetMagicWord
	/// and whether the target page is a redirect or contains the
	/// __NOAUTOLINKTARGET__ magic word.
	/// @returns boolean
	private function checkTargetPage() {
		global $wgLinkTitlesEnableNoTargetMagicWord;
		global $wgLinkTitlesCheckRedirect;

		// If checking for redirects is enabled and the target page does
		// indeed redirect to the current page, return the page title as-is
		// (unlinked).
		if ( $wgLinkTitlesCheckRedirect ) {
			$redirectTitle = $this->getTargetContent()->getUltimateRedirectTarget();
			if ( $redirectTitle && $redirectTitle->equals($this->currentTitle) ) {
				return false;
			}
		};

		// If the magic word __NOAUTOLINKTARGET__ is enabled and the target
		// page does indeed contain this magic word, return the page title
		// as-is (unlinked).
		if ( $wgLinkTitlesEnableNoTargetMagicWord ) {
			if ( $this->getTargetContent()->matchMagicWord(
					\MagicWord::get('MAG_LINKTITLES_NOTARGET') ) ) {
				return false;
			}
		};
		return true;
	}

	/// Local Debugging output function which can send output to console as well
	public function ltDebugLog($text) {
		if ( $this->config->enableDebugConsoleOutput ) {
			print $text . "\n";
		}
		wfDebugLog( 'LinkTitles', $text , 'private' );
	}

	/// Local Logging output function which can send output to console as well
	public function ltLog($text) {
		if ( $this->config->enableConsoleOutput) {
			print $text . "\n";
		}
		wfDebugLog( 'LinkTitles', $text , 'private' );
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
