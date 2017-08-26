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
	/// A Title object for the page that is being parsed.
	private static $currentTitle;

	/// A Title object for the target page currently being examined.
	private static $targetTitle;

	// The TitleValue object of the target page
	private static $targetTitleValue;

	/// The content object for the currently processed target page.
	/// This variable is necessary to be able to prevent loading the target
	/// content twice.
	private static $targetContent;

	/// Holds the page title of the currently processed target page
	/// as a string.
	private static $targetTitleText;

	public static $ltConsoleOutput;
	public static $ltConsoleOutputDebug;

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
						$newText = self::parseContent( $title, $text );
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
			$text = self::parseContent( $title, $text );
		}
		return true;
	}

	/// Core function of the extension, performs the actual parsing of the content.
	/// @param Parser $parser Parser instance for the current page
	/// @param $text          String that holds the article content
	/// @returns string: parsed text with links added if needed
	private static function parseContent( $title, &$text ) {

		// Configuration variables need to be defined here as globals.
		global $wgLinkTitlesFirstOnly;
		global $wgLinkTitlesSmartMode;
		global $wgCapitalLinks;

		( $wgLinkTitlesFirstOnly ) ? $limit = 1 : $limit = -1;
		$limitReached = false;
		self::$currentTitle = $title;
		$newText = $text;

		$config = new Config();
		$delimiters = Delimiters::default( $config );
		$targets = Targets::default( $title, $config );

		// Iterate through the page titles
		foreach( $targets->queryResult as $row ) {
			self::newTarget( $row->page_namespace, $row->page_title );

			// Don't link current page
			if ( self::$targetTitle->equals( self::$currentTitle ) ) { continue; }

			// split the page content by [[...]] groups
			// credits to inhan @ StackOverflow for suggesting preg_split
			// see http://stackoverflow.com/questions/10672286
			$arr = preg_split( $delimiters->splitter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

			// Escape certain special characters in the page title to prevent
			// regexp compilation errors
			self::$targetTitleText = self::$targetTitle->getText();
			$quotedTitle = preg_quote( self::$targetTitleText, '/' );

			self::ltDebugLog( 'TargetTitle='. self::$targetTitleText, 'private' );
			self::ltDebugLog( 'TargetTitleQuoted='. $quotedTitle, 'private' );

			// Depending on the global configuration setting $wgCapitalLinks,
			// the title has to be searched for either in a strictly case-sensitive
			// way, or in a 'fuzzy' way where the first letter of the title may
			// be either case.
			if ( $config->capitalLinks && ( $quotedTitle[0] != '\\' )) {
				$searchTerm = '((?i)' . $quotedTitle[0] . '(?-i)' .
					substr($quotedTitle, 1) . ')';
			}	else {
				$searchTerm = '(' . $quotedTitle . ')';
			}

			$regex = '/(?<![\:\.\@\/\?\&])' . $delimiters->wordStart . $searchTerm . $delimiters->wordEnd . '/S';
			for ( $i = 0; $i < count( $arr ); $i+=2 ) {
				// even indexes will point to text that is not enclosed by brackets
				$arr[$i] = preg_replace_callback( $regex,
					'LinkTitles\Extension::simpleModeCallback', $arr[$i], $limit, $count );
				if ( $config->firstOnly && ( $count > 0 ) ) {
					$limitReached = true;
					break;
				};
			};
			$newText = implode( '', $arr );

			// If smart mode is turned on, the extension will perform a second
			// pass on the page and add links with aliases where the case does
			// not match.
			if ( $config->smartMode && !$limitReached ) {
				$arr = preg_split( $delimiters->splitter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

				for ( $i = 0; $i < count( $arr ); $i+=2 ) {
					// even indexes will point to text that is not enclosed by brackets
					$arr[$i] = preg_replace_callback( '/(?<![\:\.\@\/\?\&])' .
						$delimiters->wordStart . '(' . $quotedTitle . ')' .
						$delimiters->wordEnd . '/iS', 'LinkTitles\Extension::smartModeCallback',
						$arr[$i], $limit, $count );
					if ( $config->firstOnly && ( $count > 0  )) {
						break;
					};
				};
				$newText = implode( '', $arr );
			} // $wgLinkTitlesSmartMode
		}; // foreach $res as $row
		return $newText;
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
			$newText = self::parseContent($title, $text);
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
		$withLinks = self::parseContent( $parser->getTitle(), $input );
		$output = $parser->recursiveTagParse( $withLinks, $frame );
		return $output;
	}

	// Build an anonymous callback function to be used in simple mode.
	private static function simpleModeCallback( array $matches ) {
		if ( self::checkTargetPage() ) {
			self::ltLog( "Linking '$matches[0]' to '" . self::$targetTitle . "'" );
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
	private static function smartModeCallback( array $matches ) {
		global $wgCapitalLinks;

		if ( $wgCapitalLinks ) {
			// With $wgCapitalLinks set to true we have a slightly more
			// complicated version of the callback than if it were false;
			// we need to ignore the first letter of the page titles, as
			// it does not matter for linking.
			if ( self::checkTargetPage() ) {
				self::ltLog( "Linking (smart) '$matches[0]' to '" . self::$targetTitle . "'" );
				if ( strcmp(substr(self::$targetTitleText, 1), substr($matches[0], 1)) == 0 ) {
					// Case-sensitive match: no need to bulid piped link.
					return '[[' . $matches[0] . ']]';
				} else  {
					// Case-insensitive match: build piped link.
					return '[[' . self::$targetTitleText . '|' . $matches[0] . ']]';
				}
			}
			else
			{
				return $matches[0];
			}
		} else {
			// If $wgCapitalLinks is false, we can use the simple variant
			// of the callback function.
			if ( self::checkTargetPage() ) {
				self::ltLog( "Linking (smart) '$matches[0]' to '" . self::$targetTitle . "'" );
				if ( strcmp(self::$targetTitleText, $matches[0]) == 0 ) {
					// Case-sensitive match: no need to bulid piped link.
					return '[[' . $matches[0] . ']]';
				} else  {
					// Case-insensitive match: build piped link.
					return '[[' . self::$targetTitleText . '|' . $matches[0] . ']]';
				}
			}
			else
			{
				return $matches[0];
			}
		}
	}

	/// Sets member variables for the current target page.
	private static function newTarget( $ns, $title ) {
		self::$targetTitle = \Title::makeTitleSafe( $ns, $title );
		self::ltDebugLog( 'newtarget='.  self::$targetTitle->getText(), "private" );
		self::$targetTitleValue = self::$targetTitle->getTitleValue();
		self::ltDebugLog( 'altTarget='. self::$targetTitleValue->getText(), "private" );
		self::$targetContent = null;
	}

	/// Returns the content of the current target page.
	/// This function serves to be used in preg_replace_callback callback
	/// functions, in order to load the target page content from the
	/// database only when needed.
	/// @note It is absolutely necessary that the newTarget()
	/// function is called for every new page.
	private static function getTargetContent() {
		if ( ! isset( $targetContent ) ) {
			self::$targetContent = \WikiPage::factory(
				self::$targetTitle)->getContent();
		};
		return self::$targetContent;
	}

	/// Examines the current target page. Returns true if it may be linked;
	/// false if not. This depends on the settings
	/// $wgLinkTitlesCheckRedirect and $wgLinkTitlesEnableNoTargetMagicWord
	/// and whether the target page is a redirect or contains the
	/// __NOAUTOLINKTARGET__ magic word.
	/// @returns boolean
	private static function checkTargetPage() {
		global $wgLinkTitlesEnableNoTargetMagicWord;
		global $wgLinkTitlesCheckRedirect;

		// If checking for redirects is enabled and the target page does
		// indeed redirect to the current page, return the page title as-is
		// (unlinked).
		if ( $wgLinkTitlesCheckRedirect ) {
			$redirectTitle = self::getTargetContent()->getUltimateRedirectTarget();
			if ( $redirectTitle && $redirectTitle->equals(self::$currentTitle) ) {
				return false;
			}
		};

		// If the magic word __NOAUTOLINKTARGET__ is enabled and the target
		// page does indeed contain this magic word, return the page title
		// as-is (unlinked).
		if ( $wgLinkTitlesEnableNoTargetMagicWord ) {
			if ( self::getTargetContent()->matchMagicWord(
					\MagicWord::get('MAG_LINKTITLES_NOTARGET') ) ) {
				return false;
			}
		};
		return true;
	}

	/// Local Debugging output function which can send output to console as well
	public static function ltDebugLog($text) {
		if ( self::$ltConsoleOutputDebug ) {
			print $text . "\n";
		}
		wfDebugLog( 'LinkTitles', $text , 'private' );
	}

	/// Local Logging output function which can send output to console as well
	public static function ltLog($text) {
		if (self::$ltConsoleOutput) {
			print $text . "\n";
		}
		wfDebugLog( 'LinkTitles', $text , 'private' );
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
