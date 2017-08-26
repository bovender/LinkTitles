<?php
/**
 * The LinkTitles\Extension class provides entry points for the extension.
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
 * Provides entry points for the extension.
 */
class Extension {

	/// Event handler that is hooked to the PageContentSave event.
	public static function onPageContentSave( &$wikiPage, &$user, &$content, &$summary,
			$isMinor, $isWatch, $section, &$flags, &$status ) {
		global $wgLinkTitlesParseOnEdit;
		global $wgLinkTitlesNamespaces;
		if ( !$wgLinkTitlesParseOnEdit ) return true;

		if ( !$isMinor ) {
			$title = $wikiPage->getTitle();

			// Only process if page is in one of our namespaces we want to link
			// Fixes ugly autolinking of sidebar pages
			if ( in_array( $title->getNamespace(), $wgLinkTitlesNamespaces )) {
				$text = $content->getContentHandler()->serializeContent( $content );
				if ( !\MagicWord::get( 'MAG_LINKTITLES_NOAUTOLINKS' )->match( $text ) ) {
					$newText = Linker::linkContent( $title, $text );
					if ( $newText != $text ) {
						$content = $content->getContentHandler()->unserializeContent( $newText );
					}
				}
			}
		};
		return true;
	}

	/// Event handler that is hooked to the InternalParseBeforeLinks event.
	/// @param Parser $parser Parser that raised the event.
	/// @param $text          Preprocessed text of the page.
	public static function onInternalParseBeforeLinks( \Parser &$parser, &$text ) {
		global $wgLinkTitlesParseOnRender;
		if (!$wgLinkTitlesParseOnRender) return true;
		global $wgLinkTitlesNamespaces;
		$title = $parser->getTitle();

		// If the page contains the magic word '__NOAUTOLINKS__', do not parse it.
		// Only process if page is in one of our namespaces we want to link
		if ( !\MagicWord::get( 'MAG_LINKTITLES_NOAUTOLINKS' )->match( $text ) &&
				in_array( $title->getNamespace(), $wgLinkTitlesNamespaces ) ) {
			$text = Linker::linkContent( $title, $text );
		}
		return true;
	}

	/// Automatically processes a single page, given a $title Title object.
	/// This function is called by the SpecialLinkTitles class and the
	/// LinkTitlesJob class.
	/// @param Title 					$title            Title object.
	/// @param RequestContext $context					Current request context.
	///                  If in doubt, call MediaWiki's `RequestContext::getMain()`
	///                  to obtain such an object.
	/// @returns boolean True if the page exists, false if the page does not exist
	public static function processPage( \Title $title, \RequestContext $context ) {
		self::ltLog('Processing '. $title->getPrefixedText());
		$page = \WikiPage::factory($title);
		$content = $page->getContent();
		if ( $content != null ) {
			$text = $content->getContentHandler()->serializeContent($content);
			$newText = Linker::linkContent($title, $text);
			if ( $text != $newText ) {
				$content = $content->getContentHandler()->unserializeContent( $newText );
				$page->doEditContent(
					$content,
					"Links to existing pages added by LinkTitles bot.", // TODO: i18n
					EDIT_MINOR | EDIT_FORCE_BOT,
					false, // baseRevId
					$context->getUser()
				);
			};
			return true;
		}
		else {
			return false;
		}
	}

	/// Adds the two magic words defined by this extension to the list of
	/// 'double-underscore' terms that are automatically removed before a
	/// page is displayed.
	/// @param $doubleUnderscoreIDs Array of magic word IDs.
	/// @return true
	public static function onGetDoubleUnderscoreIDs( array &$doubleUnderscoreIDs ) {
		$doubleUnderscoreIDs[] = 'MAG_LINKTITLES_NOTARGET';
		$doubleUnderscoreIDs[] = 'MAG_LINKTITLES_NOAUTOLINKS';
		return true;
	}

	public static function onParserFirstCallInit( \Parser $parser ) {
		$parser->setHook( 'noautolinks', 'LinkTitles\Extension::doNoautolinksTag' );
		$parser->setHook( 'autolinks', 'LinkTitles\Extension::doAutolinksTag' );
	}

	///	Removes the extra tag that this extension provides (<noautolinks>)
	///	by simply returning the text between the tags (if any).
	///	See https://www.mediawiki.org/wiki/Manual:Tag_extensions#Example
	public static function doNoautolinksTag( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		return htmlspecialchars( $input );
	}

	///	Removes the extra tag that this extension provides (<noautolinks>)
	///	by simply returning the text between the tags (if any).
	///	See https://www.mediawiki.org/wiki/Manual:Tag_extensions#How_do_I_render_wikitext_in_my_extension.3F
	public static function doAutolinksTag( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		$withLinks = Linker::linkContent( $parser->getTitle(), $input );
		$output = $parser->recursiveTagParse( $withLinks, $frame );
		return $output;
	}

}

// vim: ts=2:sw=2:noet:comments^=\:///
