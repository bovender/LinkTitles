<?php
/**
 * The LinkTitles\Extension class provides event handlers and entry points for the extension.
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
 * Provides event handlers and entry points for the extension.
 */
class Extension {

	/**
	 * Event handler for the PageContentSave hook.
	 *
	 * This handler is used if the parseOnEdit configuration option is set.
	 */
	public static function onPageContentSave( &$wikiPage, &$user, &$content, &$summary,
			$isMinor, $isWatch, $section, &$flags, &$status ) {
		$config = new Config();
		if ( !$config->parseOnEdit || $isMinor ) return true;
		$source = Source::createFromPageandContent( $wikiPage, $content, $config );
		$linker = new Linker( $config );
		$result = $linker->linkContent( $source );
		if ( $result ) {
			$content = $source->setText( $result );
		}
		return true;
	}

	/*
	 * Event handler for the InternalParseBeforeLinks hook.
	 *
	 * This handler is used if the parseOnRender configuration option is set.
	 */
	public static function onInternalParseBeforeLinks( \Parser &$parser, &$text ) {
		$config = new Config();
		if ( !$config->parseOnRender ) return true;
		$title = $parser->getTitle();
		$source = Source::createFromParserAndText( $parser, $text, $config );
		$linker = new Linker( $config );
		$result = $linker->linkContent( $source );
		if ( $result ) {
			$text = $result;
		}
		return true;
	}

	/**
	 * Adds links to a single page.
	 *
	 * Entry point for the SpecialLinkTitles class and the LinkTitlesJob class.
	 *
	 * @param  \Title $title Title object.
	 * @param  \RequestContext $context Current request context. If in doubt, call MediaWiki's `RequestContext::getMain()` to obtain such an object.
	 * @return bool True if the page exists, false if the page does not exist
	 */
	public static function processPage( \Title $title, \RequestContext $context ) {
		$config = new Config();
		$source = Source::createFromTitle( $title, $config );
		if ( $source->hasContent() ) {
			$linker = new Linker( $config );
			$result = $linker->linkContent( $source );
			if ( $result ) {
				$content = $source->getContent()->getContentHandler()->unserializeContent( $result );
				$source->getPage()->doEditContent(
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

	/*
	 * Adds the two magic words defined by this extension to the list of
	 * 'double-underscore' terms that are automatically removed before a
	 * page is displayed.
	 *
	 * @param  Array $doubleUnderscoreIDs Array of magic word IDs.
	 * @return true
	 */
	public static function onGetDoubleUnderscoreIDs( array &$doubleUnderscoreIDs ) {
		$doubleUnderscoreIDs[] = 'MAG_LINKTITLES_NOTARGET';
		$doubleUnderscoreIDs[] = 'MAG_LINKTITLES_NOAUTOLINKS';
		return true;
	}

	/**
	 * Handles the ParserFirstCallInit hook and adds the <autolink>/</noautolink>
	 * tags.
	 */
	public static function onParserFirstCallInit( \Parser $parser ) {
		$parser->setHook( 'noautolinks', 'LinkTitles\Extension::doNoautolinksTag' );
		$parser->setHook( 'autolinks', 'LinkTitles\Extension::doAutolinksTag' );
	}

	/*
	 *	Removes the extra tag that this extension provides (<noautolinks>)
	 *	by simply returning the text between the tags (if any).
	 *	See https://www.mediawiki.org/wiki/Manual:Tag_extensions#Example
	 */
	public static function doNoautolinksTag( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		return $parser->recursiveTagParse( $input, $frame );
	}

	/*
	 *	Removes the extra tag that this extension provides (<noautolinks>)
	 *	by simply returning the text between the tags (if any).
	 *	See https://www.mediawiki.org/wiki/Manual:Tag_extensions#How_do_I_render_wikitext_in_my_extension.3F
	 */
	public static function doAutolinksTag( $input, array $args, \Parser $parser, \PPFrame $frame ) {
		$config = new Config();
		$linker = new Linker( $config );
		$source = Source::createFromParser( $parser, $config );
		$result = $linker->linkContent( $source );
		if ( $result ) {
			return $parser->recursiveTagParse( $result, $frame );
		} else {
			return $parser->recursiveTagParse( $input, $frame );
		}
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
