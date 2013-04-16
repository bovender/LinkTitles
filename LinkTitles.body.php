<?php
/*
 *      \file LinkTitles.body.php
 *      
 *      Copyright 2012-2013 Daniel Kraus <krada@gmx.net> ('bovender')
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
 
  if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
	}

	function dump($var) {
			error_log(print_r($var, TRUE) . "\n", 3, 'php://stderr');
	};

	class LinkTitles {
		static $safeTitle;

		/// Setup function, hooks the extension's functions to MediaWiki events.
		public static function setup() {
			global $wgLinkTitlesParseOnEdit;
			global $wgLinkTitlesParseOnRender;
			global $wgHooks;
			if ( $wgLinkTitlesParseOnEdit ) {
				$wgHooks['ArticleSave'][] = 'LinkTitles::onArticleSave';
			};
			if ( $wgLinkTitlesParseOnRender ) { 
				$wgHooks['ArticleAfterFetchContent'][] = 'LinkTitles::onArticleAfterFetchContent';
			};
			$wgHooks['ParserBeforeTidy'][] = 'LinkTitles::removeMagicWord';
		}

		/// This function is hooked to the ArticleSave event.
		/// It will be called whenever a page is about to be 
		/// saved.
		public static function onArticleSave( &$article, &$user, &$text, &$summary,
				$minor, $watchthis, $sectionanchor, &$flags, &$status ) {

			// To prevent time-consuming parsing of the page whenever
			// it is edited and saved, we only parse it if the flag
			// 'minor edits' is not set.
			return $minor or self::parseContent( $article, $text );
		}

		/// Called when an ArticleAfterFetchContent event occurs; this requires the
		/// $wgLinkTitlesParseOnRender option to be set to 'true'
		public static function onArticleAfterFetchContent( &$article, &$content ) {
			// The ArticleAfterFetchContent event is triggered whenever page content
			// is retrieved from the database, i.e. also for editing etc.
			// Therefore we access the global $action variabl to only parse the 
			// content when the page is viewed.
			global $action;
			if ( in_array( $action, array('view', 'render', 'purge') ) ) {
				self::parseContent( $article, $content );
			};
			return true;
		}

		/// This function performs the actual parsing of the content.
		static function parseContent( &$article, &$text ) {

			// If the page contains the magic word '__NOAUTOLINKS__', do not parse
			// the content.
			$mw = MagicWord::get('MAG_LINKTITLES_NOAUTOLINKS');
			if ( $mw -> match( $text ) ) {
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
			// global $wgLinkTitlesIgnoreCase;
			global $wgLinkTitlesSmartMode;
			global $wgCapitalLinks;

			( $wgLinkTitlesWordStartOnly ) ? $wordStartDelim = '\b' : $wordStartDelim = '';
			( $wgLinkTitlesWordEndOnly ) ? $wordEndDelim = '\b' : $wordEndDelim = '';
			// ( $wgLinkTitlesIgnoreCase ) ? $regexModifier = 'i' : $regexModifier = '';

			// To prevent adding self-references, we now
			// extract the current page's title.
			$myTitle = $article->getTitle()->getText();

			( $wgLinkTitlesPreferShortTitles ) ? $sort_order = 'ASC' : $sort_order = 'DESC';
			( $wgLinkTitlesFirstOnly ) ? $limit = 1 : $limit = -1;

			if ( $wgLinkTitlesSkipTemplates )
			{
				$templatesDelimiter = '{{.+}}';
			} else {
				$templatesDelimiter = '{{[^|]+?}}|{{.+\|';
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
			$delimiter = '/(' . $delimiter . '\[\[.*?\]\]|' . $templatesDelimiter . 
				'|\[' . $urlPattern . '\s.+?\]|'. $urlPattern . 
				'(?=\s|$)|(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b))/i';

			$black_list = str_replace( '_', ' ',
				'("' . implode( '", "',$wgLinkTitlesBlackList ) . '")' );

			// Depending on the global setting $wgCapitalLinks, we need
			// different callback functions further down.
			if ( $wgCapitalLinks ) {
				$callBack = "LinkTitles::CallBackCaseInsensitive";
			}	else {
				$callBack = "LinkTitles::CallBackCaseSensitive";
			}

			// Build an SQL query and fetch all page titles ordered
			// by length from shortest to longest.
			// Only titles from 'normal' pages (namespace uid = 0)
			// are returned.
			$dbr = wfGetDB( DB_SLAVE );
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

			// Iterate through the page titles
			foreach( $res as $row ) {
				// Page titles are stored in the database with spaces
				// replaced by underscores. Therefore we now convert
				// the underscores back to spaces.
				$title = str_replace('_', ' ', $row->page_title);

				if ( $title != $myTitle ) {
					LinkTitles::$safeTitle = str_replace( '/', '\/', $title );

					// split the string by [[...]] groups
					// credits to inhan @ StackOverflow for suggesting preg_split
					// see http://stackoverflow.com/questions/10672286
					$arr = preg_split( $delimiter, $text, -1, PREG_SPLIT_DELIM_CAPTURE );

					// Depending on the global configuration setting $wgCapitalLinks,
					// the title has to be searched for either in a strictly case-sensitive
					// way, or in a 'fuzzy' way where the first letter of the title may
					// be either case.
					if ( $wgCapitalLinks ) {
						$searchTerm = '((?i)' . LinkTitles::$safeTitle[0] . '(?-i)' . 
							substr(LinkTitles::$safeTitle, 1) . ')';
					}	else {
						$searchTerm = '(' . LinkTitles::$safeTitle . ')';
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
					$text = implode( '', $arr );

					// If smart mode is turned on, the extension will perform a second
					// pass on the page and add links with aliases where the case does
					// not match.
					if ($wgLinkTitlesSmartMode) {
						// split the string by [[...]] groups
						// credits to inhan @ StackOverflow for suggesting preg_split
						// see http://stackoverflow.com/questions/10672286
						$arr = preg_split( $delimiter, $text, -1, PREG_SPLIT_DELIM_CAPTURE );

						for ( $i = 0; $i < count( $arr ); $i+=2 ) {
							// even indexes will point to text that is not enclosed by brackets
							$arr[$i] = preg_replace_callback( '/(?<![\:\.\@\/\?\&])' .
								$wordStartDelim . '(' . LinkTitles::$safeTitle . ')' . 
								$wordEndDelim . '/i', $callBack, $arr[$i], $limit, $count );
							if (( $limit >= 0 ) && ( $count > 0  )) {
								break; 
							};
						};
						$text = implode( '', $arr );
					}
				}; // if $title != $myTitle
			}; // foreach $res as $row
			return true;
		}

		static function CallBackCaseInsensitive($matches) {
			if ( strcmp(substr(LinkTitles::$safeTitle, 1), substr($matches[0], 1)) == 0 ) {
				return '[[' . $matches[0] . ']]';
			} else  {
				return '[[' . LinkTitles::$safeTitle . '|' . $matches[0] . ']]';
			}
		}

		static function CallBackCaseSensitive($matches) {
			if ( strcmp(substr(LinkTitles::$safeTitle, 0), substr($matches[0], 0)) == 0 ) {
				return '[[' . $matches[0] . ']]';
			} else  {
				return '[[' . LinkTitles::$safeTitle . '|' . $matches[0] . ']]';
			}
		}

		static function removeMagicWord( &$parser, &$text ) {
			$mw = MagicWord::get('MAG_LINKTITLES_NOAUTOLINKS');
			$mw -> matchAndRemove( $text );
			return true;
		}
	}

	// vim: ts=2:sw=2:noet
