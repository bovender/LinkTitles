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
		/// Setup function, hooks the extension's functions to MediaWiki events.
		public static function setup() {
			global $wgLinkTitlesParseOnEdit;
			global $wgLinkTitlesParseOnRender;
			global $wgHooks;
			if ( $wgLinkTitlesParseOnEdit ) {
				$wgHooks['PageContentSave'][] = 'LinkTitles::onPageContentSave';
			};
			if ( $wgLinkTitlesParseOnRender ) { 
				$wgHooks['ArticleAfterFetchContentObject'][] =
				 		'LinkTitles::onArticleAfterFetchContentObject';
			};
			$wgHooks['ParserBeforeTidy'][] = 'LinkTitles::removeMagicWord';
		}

		/// Event handler that is hooked to the ArticleSave event.
		public static function onPageContentSave( &$wikiPage, &$user, &$content, &$summary,
				$isMinor, $isWatch, $section, &$flags, &$status ) {

			// To prevent time-consuming parsing of the page whenever
			// it is edited and saved, we only parse it if the flag
			// 'minor edits' is not set.
			return $isMinor or self::parseContent( $wikiPage, $content );
		}

		/// Event handler that is hooked to the ArticleAfterFetchContent event.
		/// @param $article Article object
		/// @param $content Content object that holds the article content
		public static function onArticleAfterFetchContentObject( &$article, &$content ) {
			// The ArticleAfterFetchContentObject event is triggered whenever page 
			// content is retrieved from the database, i.e. also for editing etc.
			// Therefore we access the global $action variabl to only parse the 
			// content when the page is viewed.
			global $action;
			if ( in_array( $action, array('view', 'render', 'purge') ) ) {
				self::parseContent( $article, $content );
			};
			return true;
		}

		/// Core function of the extension, performs the actual parsing of the content.
		/// @param $article Article object
		/// @param $content Content object that holds the article content
		/// @returns true
		static function parseContent( &$article, &$content ) {

			// If the page contains the magic word '__NOAUTOLINKS__', do not parse
			// the content.
			if ( $content->matchMagicWord(
					MagicWord::get('MAG_LINKTITLES_NOAUTOLINKS') ) ) {
				return true;
			}

			// Configuration variables need to be defined here as globals.
			global $wgLinkTitlesPreferShortTitles;
			global $wgLinkTitlesMinimumTitleLength;
			global $wgLinkTitlesParseHeadings;
			global $wgLinkTitlesBlackList;
			global $wgLinkTitlesSkipTemplates;
			global $wgLinkTitlesFirstOnly;
			global $wgLinkTitlesWordStartOnly;
			global $wgLinkTitlesWordEndOnly;
			global $wgLinkTitlesSmartMode;
			global $wgCapitalLinks;
			global $wgLinkTitlesEnableNoTargetMagicWord;
			global $wgLinkTitlesCheckRedirect;

			( $wgLinkTitlesWordStartOnly ) ? $wordStartDelim = '\b' : $wordStartDelim = '';
			( $wgLinkTitlesWordEndOnly ) ? $wordEndDelim = '\b' : $wordEndDelim = '';
			// ( $wgLinkTitlesIgnoreCase ) ? $regexModifier = 'i' : $regexModifier = '';

			// To prevent adding self-references, we now
			// extract the current page's title.
			$myTitle = $article->getTitle();
			$myTitleText = $myTitle->GetText();

			( $wgLinkTitlesPreferShortTitles ) ? $sort_order = 'ASC' : $sort_order = 'DESC';
			( $wgLinkTitlesFirstOnly ) ? $limit = 1 : $limit = -1;

			if ( $wgLinkTitlesSkipTemplates )
			{
				$templatesDelimiter = '{{.+?}}|';
			} else {
				$templatesDelimiter = '{{[^|]+?}}|{{.+\||';
			};

			// Build a regular expression that will capture existing wiki links ("[[...]]"),
			// wiki headings ("= ... =", "== ... ==" etc.),  
			// urls ("http://example.com", "[http://example.com]", "[http://example.com Description]",
			// and email addresses ("mail@example.com").
			// Since there is a user option to skip headings, we make this part of the expression
			// optional. Note that in order to use preg_split(), it is important to have only one
			// capturing subpattern (which precludes the use of conditional subpatterns).
			( $wgLinkTitlesParseHeadings ) ? $delimiter = '' : $delimiter = '=+.+?=+|';
			$urlPattern = '[a-z]+?\:\/\/(?:\S+\.)+\S+(?:\/.*)?';
			$delimiter = '/(' .                           // exclude from linking:
				'\[\[.*?\]\]|' .                            // links
				$delimiter .                                // titles (if requested)
				$templatesDelimiter .                       // templates (if requested)
				'^ .+?\n|\n .+?\n|\n .+?$|^ .+?$|' .        // preformatted text
				'<nowiki>.*?<.nowiki>|<code>.*?<\/code>|' . // nowiki/code
				'<pre>.*?<\/pre>|<html>.*?<\/html>|' .      // pre/html
				'<script>.*?<\/script>|' .                  // script
				'<div.+?>|<\/div>|' .                       // attributes of div elements
				'<span.+?>|<\/span>|' .                     // attributes of span elements
				'style=".+?"|class=".+?"|' .                // styles and classes (e.g. of wikitables)
				'\[' . $urlPattern . '\s.+?\]|'. $urlPattern .  '(?=\s|$)|' . // urls
				'(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b)' .        // email addresses
				')/i';

			// Build a blacklist of pages that are not supposed to be link 
			// targets. This includes the current page.
			$black_list = str_replace( '_', ' ',
				'("' . implode( '", "',$wgLinkTitlesBlackList ) . 
				$myTitle->getDbKey() . '")' );


			// Build an SQL query and fetch all page titles ordered by length from 
			// shortest to longest. Only titles from 'normal' pages (namespace uid 
			// = 0) are returned. Since the db may be sqlite, we need a try..catch 
			// structure because sqlite does not support the CHAR_LENGTH function.
			$dbr = wfGetDB( DB_SLAVE );
			try {
				$res = $dbr->select( 
					'page', 
					'page_title', 
					array( 
						'page_namespace = 0', 
						'CHAR_LENGTH(page_title) >= ' . $wgLinkTitlesMinimumTitleLength,
						'page_title NOT IN ' . $black_list,
					), 
					__METHOD__, 
					array( 'ORDER BY' => 'CHAR_LENGTH(page_title) ' . $sort_order )
				);
			} catch (Exception $e) {
				$res = $dbr->select( 
					'page', 
					'page_title', 
					array( 
						'page_namespace = 0', 
						'LENGTH(page_title) >= ' . $wgLinkTitlesMinimumTitleLength,
						'page_title NOT IN ' . $black_list,
					), 
					__METHOD__, 
					array( 'ORDER BY' => 'LENGTH(page_title) ' . $sort_order )
				);
			}

			$text = $content->getContentHandler()->serializeContent($content);

			// Iterate through the page titles
			foreach( $res as $row ) {
				// Obtain an instance of a Title class for the current database row.
				$targetTitle = Title::makeTitle(NS_MAIN, $row->page_title);

				if ( $wgLinkTitlesCheckRedirect || $wgLinkTitlesEnableNoTargetMagicWord ) {
					// Obtain a page object for the current title, so we can check for 
					// the presence of the __NOAUTOLINKTARGET__ magic keyword.
					$targetPageContent = WikiPage::factory($targetTitle)->getContent();

					// To prevent linking to pages that redirect to the current page,
					// obtain the title that the target page redirects to. Will be null 
					// if there is no redirect.
					if ( $wgLinkTitlesCheckRedirect ) {
						$redirectTitle = $targetPageContent->getUltimateRedirectTarget();
						$redirectCheck = !( $redirectTitle && $redirectTitle->equals($myTitle) );
					}
					else
					{
						$redirectCheck = true;
					};

					if ( $wgLinkTitlesEnableNoTargetMagicWord ) {
						$magicWordCheck = ! $targetPageContent->matchMagicWord(
							MagicWord::get('MAG_LINKTITLES_NOTARGET') ); 
					}
					else 
					{
						$magicWordCheck = true;
					};
				}
				else
				{
					$redirectCheck = true;
					$magicWordCheck = true;
				}

				// Proceed only if the currently examined page does not redirect to 
				// our page and does not contain the no-target magic word.
				// If the corresponding configuration variables are set to false,
				// both 'check' variables below will be set to true by the code 
				// above.
				if ( $redirectCheck && $magicWordCheck ) {
					// split the page content by [[...]] groups
					// credits to inhan @ StackOverflow for suggesting preg_split
					// see http://stackoverflow.com/questions/10672286
					$arr = preg_split( $delimiter, $text, -1, PREG_SPLIT_DELIM_CAPTURE );

					// Escape certain special characters in the page title to prevent
					// regexp compilation errors
					$targetTitleText = $targetTitle->getText();
					$quotedTitle = preg_quote($targetTitleText, '/');

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
						$arr[$i] = preg_replace( '/(?<![\:\.\@\/\?\&])' .
							$wordStartDelim . $searchTerm . $wordEndDelim . '/',
							'[[$1]]', $arr[$i], $limit, $count );
						if (( $limit >= 0 ) && ( $count > 0  )) {
							break; 
						};
					};
					$newText = implode( '', $arr );

					// If smart mode is turned on, the extension will perform a second
					// pass on the page and add links with aliases where the case does
					// not match.
					if ($wgLinkTitlesSmartMode) {
						// Build a callback function for use with preg_replace_callback.
						// This essentially performs a case-sensitive comparison of the 
						// current page title and the occurrence found on the page; if 
						// the cases do not match, it builds an aliased (piped) link.
						// If $wgCapitalLinks is set to true, the case of the first 
						// letter is ignored by MediaWiki and we don't need to build a 
						// piped link if only the case of the first letter is different.
						// For good performance, we use two different callback 
						// functions.
						if ( $wgCapitalLinks ) {
							// With $wgCapitalLinks set to true we have a slightly more 
							// complicated version of the callback than if it were false; 
							// we need to ignore the first letter of the page titles, as 
							// it does not matter for linking.
							$callback = function ($matches) use ($targetTitleText) {
								if ( strcmp(substr($targetTitleText, 1), substr($matches[0], 1)) == 0 ) {
									// Case-sensitive match: no need to bulid piped link.
									return '[[' . $matches[0] . ']]';
								} else  {
									// Case-insensitive match: build piped link.
									return '[[' . $targetTitleText . '|' . $matches[0] . ']]';
								}
							};
						}
						else
						{
							// If $wgCapitalLinks is false, we can use the simple variant 
							// of the callback function.
							$callback = function ($matches) use ($targetTitleText) {
								if ( strcmp($targetTitleText, $matches[0]) == 0 ) {
									// Case-sensitive match: no need to bulid piped link.
									return '[[' . $matches[0] . ']]';
								} else  {
									// Case-insensitive match: build piped link.
									return '[[' . $targetTitleText . '|' . $matches[0] . ']]';
								}
							};
						}

						$arr = preg_split( $delimiter, $newText, -1, PREG_SPLIT_DELIM_CAPTURE );

						for ( $i = 0; $i < count( $arr ); $i+=2 ) {
							// even indexes will point to text that is not enclosed by brackets
							$arr[$i] = preg_replace_callback( '/(?<![\:\.\@\/\?\&])' .
								$wordStartDelim . '(' . $quotedTitle . ')' . 
								$wordEndDelim . '/i', $callback, $arr[$i], $limit, $count );
							if (( $limit >= 0 ) && ( $count > 0  )) {
								break; 
							};
						};
						$newText = implode( '', $arr );
 						if ( $newText != $text ) {
 							$content = $content->getContentHandler()->unserializeContent( $newText );
 						}
					} // $wgLinkTitlesSmartMode
				}
			}; // foreach $res as $row
			return true;
		}
		
		/// Automatically processes a single page, given a $title Title object.
		/// This function is called by the SpecialLinkTitles class and the 
		/// LinkTitlesJob class.
		/// @param $title    `Title` object that identifies the page.
		/// @param $context  Object that implements IContextProvider.
		///                  If in doubt, call MediaWiki's `RequestContext::getMain()`
		///                  to obtain such an object.
		/// @returns undefined
		public static function processPage($title, $context) {
			// TODO: make this namespace-aware
			$titleObj = Title::makeTitle(0, $title);
			$page = WikiPage::factory($titleObj);
			$content = $page->getContent();
			$article = Article::newFromWikiPage($page, $context);
			LinkTitles::parseContent($article, $content);
			$page->doEditContent($content,
				"Links to existing pages added by LinkTitles bot.",
				EDIT_MINOR | EDIT_FORCE_BOT,
				$context->getUser()
			);
		}

		/// Remove the magic words that this extension introduces from the 
		/// $text, so that they do not appear on the rendered page.
		/// @param $parser Parser object
		/// @param $text   String that contains the page content.
		/// @returns true
		static function removeMagicWord( &$parser, &$text ) {
			$mwa = new MagicWordArray(array(
				'MAG_LINKTITLES_NOAUTOLINKS',
				'MAG_LINKTITLES_NOTARGET'
				)
			);
			$mwa->matchAndRemove( $text );
			return true;
		}
	}

// vim: ts=2:sw=2:noet:comments^=\:///
