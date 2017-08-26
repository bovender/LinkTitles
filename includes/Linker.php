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
 * Provides entry points for the extension.
 */
class Linker {
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

	/// Core function of the extension, performs the actual parsing of the content.
	/// @param Parser $parser Parser instance for the current page
	/// @param $text          String that holds the article content
	/// @returns string: parsed text with links added if needed
	public static function linkContent( $title, &$text ) {

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
					'LinkTitles\Linker::simpleModeCallback', $arr[$i], $limit, $count );
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
						$delimiters->wordEnd . '/iS', 'LinkTitles\Linker::smartModeCallback',
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
