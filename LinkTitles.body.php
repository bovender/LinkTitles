<?php
/*
 *      Copyright 2012-2014 Daniel Kraus <krada@gmx.net> ('bovender')
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */
	/// @file
 
	/// Helper function for development and debugging.
  /// @param $var Any variable. Raw content will be dumped to stderr.
	/// @return undefined
	function dump($var) {
			error_log(print_r($var, TRUE) . "\n", 3, 'php://stderr');
	};

	/// Central class of the extension. Sets up parser hooks.
	/// This class contains only static functions; do not instantiate.
	class LinkTitles {
		/// A Title object for the page that is being parsed.
		private static $currentTitle;

		/// A Title object for the target page currently being examined.
		private static $targetTitle;

		/// The content object for the currently processed target page.
		/// This variable is necessary to be able to prevent loading the target 
		/// content twice.
		private static $targetContent;

		/// Holds the page title of the currently processed target page
		/// as a string.
		private static $targetTitleText;

		/// Delimiter used in a regexp split operation to seperate those parts
		/// of the page that should be parsed from those that should not be
		/// parsed (e.g. inside pre-existing links etc.).
		private static $delimiter;

		private static $wordStartDelim;
		private static $wordEndDelim;
		
		/// Setup function, hooks the extension's functions to MediaWiki events.
		public static function setup() {
			global $wgLinkTitlesParseOnEdit;
			global $wgLinkTitlesParseOnRender;
			global $wgHooks;
			if ( $wgLinkTitlesParseOnEdit ) {
				$wgHooks['PageContentSave'][] = 'LinkTitles::onPageContentSave';
			};
			if ( $wgLinkTitlesParseOnRender ) { 
				$wgHooks['InternalParseBeforeLinks'][] = 'LinkTitles::onInternalParseBeforeLinks';
			};
			$wgHooks['GetDoubleUnderscoreIDs'][] = 'LinkTitles::onGetDoubleUnderscoreIDs';
			self::BuildDelimiters();
		}

		/// Event handler that is hooked to the PageContentSave event.
		public static function onPageContentSave( &$wikiPage, &$user, &$content, &$summary,
				$isMinor, $isWatch, $section, &$flags, &$status ) {

			if ( ! $isMinor ) {
				$title = $wikiPage->getTitle();
				$text = $content->getContentHandler()->serializeContent($content);
				$newText = self::parseContent( $title, $text );
				if ( $newText != $text ) {
					$content = $content->getContentHandler()->unserializeContent( $newText );
				}
			};
			return true;
		}

		/// Event handler that is hooked to the InternalParseBeforeLinks event.
		/// @param Parser $parser Parser that raised the event.
		/// @param $text          Preprocessed text of the page.
		public static function onInternalParseBeforeLinks( Parser &$parser, &$text ) {
			$title = $parser->getTitle();
			$text = self::parseContent( $title, $text );
			return true;
		}

		/// Core function of the extension, performs the actual parsing of the content.
		/// @param Title $title   Title of the page being parsed
		/// @param $text          String that holds the article content
		/// @returns string: parsed text with links added if needed
		private static function parseContent( Title &$title, &$text ) {
			// If the page contains the magic word '__NOAUTOLINKS__', do not parse it.
			if ( MagicWord::get('MAG_LINKTITLES_NOAUTOLINKS')->match( $text ) ) {
				return $text;
			}

			// Configuration variables need to be defined here as globals.
			global $wgLinkTitlesPreferShortTitles;
			global $wgLinkTitlesMinimumTitleLength;
			global $wgLinkTitlesBlackList;
			global $wgLinkTitlesFirstOnly;
			global $wgLinkTitlesSmartMode;
			global $wgCapitalLinks;
            global $wgLinkTitlesNamespaces;

			( $wgLinkTitlesPreferShortTitles ) ? $sort_order = 'ASC' : $sort_order = 'DESC';
			( $wgLinkTitlesFirstOnly ) ? $limit = 1 : $limit = -1;

			LinkTitles::$currentTitle = $title;
			$newText = $text;

			// Build a blacklist of pages that are not supposed to be link 
			// targets. This includes the current page.
			$blackList = str_replace( '_', ' ',
				'("' . implode( '", "',$wgLinkTitlesBlackList ) . '", "' .
				LinkTitles::$currentTitle->getDbKey() . '")' );

            $currentNamespace[] = $title->getNamespace();
            wfDebugLog("LinkTitles",'$wgLinkTitlesNamespaces = '. print_r($wgLinkTitlesNamespaces,true),'private');
            wfDebugLog("LinkTitles",'$currentNamespace = '. print_r($currentNamespace,true),'private');

            // Build our weight list. Make sure current namespace is first element
            $namespaces = array_diff($wgLinkTitlesNamespaces, $currentNamespace);
            array_unshift($namespaces,  $currentNamespace[0] );
            wfDebugLog("LinkTitles",'$namespaces = '. print_r($namespaces,true),'private');

            // No need for sanitiy check. we are sure that we have at least one element in the array
            $weightSelect = "CASE page_namespace ";
            $currentWeight = 0;
            foreach ($namespaces as &$namspacevalue) {
                $currentWeight = $currentWeight + 100;
                $weightSelect = $weightSelect . " WHEN " . $namspacevalue . " THEN " . $currentWeight . PHP_EOL;
            }
            $weightSelect = $weightSelect . " END ";

            $namespacesClause = str_replace( '_', ' ','(' . implode( ', ',$namespaces ) . ')' );

			// Build an SQL query and fetch all page titles ordered by length from 
			// shortest to longest. Only titles from 'normal' pages (namespace uid 
			// = 0) are returned. Since the db may be sqlite, we need a try..catch 
			// structure because sqlite does not support the CHAR_LENGTH function.
			$dbr = wfGetDB( DB_SLAVE );
			try {
				$res = $dbr->select( 
					'page', 
					array( 'page_title', 'page_namespace' , "weight" => $weightSelect),
					array( 		
                        'page_namespace IN ' . $namespacesClause, 
						'CHAR_LENGTH(page_title) >= ' . $wgLinkTitlesMinimumTitleLength,
						'page_title NOT IN ' . $blackList,
					), 
					__METHOD__, 
					array( 'ORDER BY' => 'weight ASC, CHAR_LENGTH(page_title) ' . $sort_order )
				);
			} catch (Exception $e) {
				$res = $dbr->select( 
					'page', 
					array( 'page_title', 'page_namespace' , "weight" => $weightSelect ),
					array( 
						'page_namespace IN ' . $namespacesClause, 
						'LENGTH(page_title) >= ' . $wgLinkTitlesMinimumTitleLength,
						'page_title NOT IN ' . $blackList,
					), 
					__METHOD__, 
					array( 'ORDER BY' => 'weight ASC, LENGTH(page_title) ' . $sort_order )
				);
			}

			// Iterate through the page titles
			foreach( $res as $row ) {
				LinkTitles::newTarget($row->page_namespace, $row->page_title );

				// split the page content by [[...]] groups
				// credits to inhan @ StackOverflow for suggesting preg_split
				// see http://stackoverflow.com/questions/10672286
				$arr = preg_split( self::$delimiter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

				// Escape certain special characters in the page title to prevent
				// regexp compilation errors
				LinkTitles::$targetTitleText = LinkTitles::$targetTitle->getText();
				$quotedTitle = preg_quote(LinkTitles::$targetTitleText, '/');

				// Depending on the global configuration setting $wgCapitalLinks,
				// the title has to be searched for either in a strictly case-sensitive
				// way, or in a 'fuzzy' way where the first letter of the title may
				// be either case.
				if ( $wgCapitalLinks && ( $quotedTitle[0] != '\\' )) {
					$searchTerm = '((?i)' . $quotedTitle[0] . '(?-i)' . 
						substr($quotedTitle, 1) . ')';
				}	else {
					$searchTerm = '(' . $quotedTitle . ')';
				}

				for ( $i = 0; $i < count( $arr ); $i+=2 ) {
					// even indexes will point to text that is not enclosed by brackets
					$arr[$i] = preg_replace_callback( '/(?<![\:\.\@\/\?\&])' .
						self::$wordStartDelim . $searchTerm . self::$wordEndDelim . '/u',
						array('LinkTitles', 'simpleModeCallback'), $arr[$i], $limit, $count );
					if (( $limit >= 0 ) && ( $count > 0  )) {
						break; 
					};
				};
				$newText = implode( '', $arr );

				// If smart mode is turned on, the extension will perform a second
				// pass on the page and add links with aliases where the case does
				// not match.
				if ($wgLinkTitlesSmartMode) {
					$arr = preg_split( self::$delimiter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

					for ( $i = 0; $i < count( $arr ); $i+=2 ) {
						// even indexes will point to text that is not enclosed by brackets
						$arr[$i] = preg_replace_callback( '/(?<![\:\.\@\/\?\&])' .
							self::$wordStartDelim . '(' . $quotedTitle . ')' . 
							self::$wordEndDelim . '/iu', array('LinkTitles', 'smartModeCallback'),
							$arr[$i], $limit, $count );
						if (( $limit >= 0 ) && ( $count > 0  )) {
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
		/// @param string $title            Page title.
		/// @param RequestContext $context  Current context. 
		///                  If in doubt, call MediaWiki's `RequestContext::getMain()`
		///                  to obtain such an object.
		/// @returns undefined
		public static function processPage(Title $title, RequestContext $context) {
			// TODO: make this namespace-aware			
			$page = WikiPage::factory($title);
			$content = $page->getContent();
			$text = $content->getContentHandler()->serializeContent($content);
			$newText = LinkTitles::parseContent($title, $text);
			if ( $text != $newText ) {
				$content = $content->getContentHandler()->unserializeContent( $newText );
				$page->doQuickEditContent($content,
					$context->getUser(),
					"Links to existing pages added by LinkTitles bot.",
					true // minor modification
				);
			};
		}

		/// Adds the two magic words defined by this extension to the list of 
		/// 'double-underscore' terms that are automatically removed before a 
		/// page is displayed.
		/// @param $doubleUnderscoreIDs Array of magic word IDs.
		/// @returns true
		public static function onGetDoubleUnderscoreIDs( array &$doubleUnderscoreIDs ) {
			$doubleUnderscoreIDs[] = 'MAG_LINKTITLES_NOTARGET';
			$doubleUnderscoreIDs[] = 'MAG_LINKTITLES_NOAUTOLINKS';
			return true;
		}

		// Build an anonymous callback function to be used in simple mode.
		private static function simpleModeCallback( array $matches ) {
			if ( LinkTitles::checkTargetPage() ) {
				return '[[' . LinkTitles::$targetTitle . "|" . $matches[0] . ']]';
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
				if ( LinkTitles::checkTargetPage() ) {
					if ( strcmp(substr(LinkTitles::$targetTitleText, 1), substr($matches[0], 1)) == 0 ) {
						// Case-sensitive match: no need to bulid piped link.
						return '[[' . $matches[0] . ']]';
					} else  {
						// Case-insensitive match: build piped link.
						return '[[' . LinkTitles::$targetTitleText . '|' . $matches[0] . ']]';
					}
				}
				else
				{
					return $matches[0];
				}
			} else {
				// If $wgCapitalLinks is false, we can use the simple variant 
				// of the callback function.
				if ( LinkTitles::checkTargetPage() ) {
					if ( strcmp(LinkTitles::$targetTitleText, $matches[0]) == 0 ) {
						// Case-sensitive match: no need to bulid piped link.
						return '[[' . $matches[0] . ']]';
					} else  {
						// Case-insensitive match: build piped link.
						return '[[' . LinkTitles::$targetTitleText . '|' . $matches[0] . ']]';
					}
				}
				else
				{
					return $matches[0];
				}
			}
		}

		/// Sets member variables for the current target page.
		private static function newTarget($ns, $title ) {
			// @todo Make this wiki namespace aware.
			LinkTitles::$targetTitle = Title::makeTitle( $ns, $title);
			LinkTitles::$targetContent = null;
		}

		/// Returns the content of the current target page.
		/// This function serves to be used in preg_replace_callback callback 
		/// functions, in order to load the target page content from the 
		/// database only when needed.
		/// @note It is absolutely necessary that the newTarget() 
		/// function is called for every new page.
		private static function getTargetContent() {
			if ( ! isset( $targetContent ) ) {
				LinkTitles::$targetContent = WikiPage::factory(
					LinkTitles::$targetTitle)->getContent();
			};
			return LinkTitles::$targetContent;
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
				$redirectTitle = LinkTitles::getTargetContent()->getUltimateRedirectTarget();
				if ( $redirectTitle && $redirectTitle->equals(LinkTitles::$currentTitle) ) {
					return false;
				}
			};

			// If the magic word __NOAUTOLINKTARGET__ is enabled and the target 
			// page does indeed contain this magic word, return the page title 
			// as-is (unlinked).
			if ( $wgLinkTitlesEnableNoTargetMagicWord ) {
				if ( LinkTitles::getTargetContent()->matchMagicWord(
						MagicWord::get('MAG_LINKTITLES_NOTARGET') ) ) {
					return false;
				}
			};
			return true;
		}

	/// Builds the delimiter that is used in a regexp to separate
	/// text that should be parsed from text that should not be
	/// parsed (e.g. inside existing links etc.)
	private static function BuildDelimiters() {
			// Configuration variables need to be defined here as globals.
			global $wgLinkTitlesParseHeadings;
			global $wgLinkTitlesSkipTemplates;
			global $wgLinkTitlesWordStartOnly;
			global $wgLinkTitlesWordEndOnly;

			// Use unicode character properties rather than \b escape sequences
			// to detect whole words containing non-ASCII characters as well.
			// Note that this requires the use of the '/u' switch, and you need
			// to have PHP with a PCRE library that was compiled with 
			// --enable-unicode-properties
			( $wgLinkTitlesWordStartOnly ) ? self::$wordStartDelim = '(?<!\pL)' : self::$wordStartDelim = '';
			( $wgLinkTitlesWordEndOnly ) ? self::$wordEndDelim = '(?!\pL)' : self::$wordEndDelim = '';

			if ( $wgLinkTitlesSkipTemplates )
			{
				$templatesDelimiter = '{{[^}]+}}|';
			} else {
				// Match template names (ignoring any piped [[]] links in them) 
				// along with the trailing pipe and parameter name or closing 
				// braces; also match sequences of '|wordcharacters=' (without 
				// spaces in them) that usually only occur as parameter names in 
				// transclusions (but could also occur as wiki table cell contents).
				// TODO: Find a way to match parameter names in transclusions, but 
				// not in table cells or other sequences involving a pipe character 
				// and equal sign.
				$templatesDelimiter = '{{[^|]*?(?:(?:\[\[[^]]+]])?)[^|]*?(?:\|(?:\w+=)?|(?:}}))|\|\w+=|';
			}

			// Build a regular expression that will capture existing wiki links ("[[...]]"),
			// wiki headings ("= ... =", "== ... ==" etc.),  
			// urls ("http://example.com", "[http://example.com]", "[http://example.com Description]",
			// and email addresses ("mail@example.com").
			// Since there is a user option to skip headings, we make this part of the expression
			// optional. Note that in order to use preg_split(), it is important to have only one
			// capturing subpattern (which precludes the use of conditional subpatterns).
			( $wgLinkTitlesParseHeadings ) ? $delimiter = '' : $delimiter = '=+.+?=+|';
			$urlPattern = '[a-z]+?\:\/\/(?:\S+\.)+\S+(?:\/.*)?';
			self::$delimiter = '/(' .                     // exclude from linking:
				'\[\[.*?\]\]|' .                            // links
				$delimiter .                                // titles (if requested)
				$templatesDelimiter .                       // templates (if requested)
				'^ .+?\n|\n .+?\n|\n .+?$|^ .+?$|' .        // preformatted text
				'<nowiki>.*?<.nowiki>|<code>.*?<\/code>|' . // nowiki/code
				'<pre>.*?<\/pre>|<html>.*?<\/html>|' .      // pre/html
				'<script>.*?<\/script>|' .                  // script
				'<div.+?>|<\/div>|' .                       // attributes of div elements
				'<span.+?>|<\/span>|' .                     // attributes of span elements
				'<file>[^<]*<\/file>|' .                    // stuff inside file elements
				'style=".+?"|class=".+?"|' .                // styles and classes (e.g. of wikitables)
				'\[' . $urlPattern . '\s.+?\]|'. $urlPattern .  '(?=\s|$)|' . // urls
				'(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b)' .        // email addresses
				')/ism';
			}
		}

// vim: ts=2:sw=2:noet:comments^=\:///
