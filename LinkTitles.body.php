<?php
/*
 *      \file LinkTitles.body.php
 *      
 *      Copyright 2012 Daniel Kraus <krada@gmx.net>
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
			error_log(print_r($var, TRUE), 3, 'php://stderr');
	};

	class LinkTitles {

		/// This function is hooked to the ArticleSave event.
		/// It will be called whenever a page is about to be 
		/// saved.
		public static function onArticleSave( &$article, &$user, &$text, &$summary,
				$minor, $watchthis, $sectionanchor, &$flags, &$status ) {

			// To prevent time-consuming parsing of the page whenever
			// it is edited and saved, we only parse it if the flag
			// 'minor edits' is not set.

			if ( !$minor ) {
				// To prevent adding self-references, we now
				// extract the current page's title.
				$my_title = $article->getTitle()->getText();

				// Build an SQL query and fetch all page titles ordered
				// by length from shortest to longest.
				// Only titles from 'normal' pages (namespace uid = 0)
				// are returned.
				$dbr = wfGetDB( DB_SLAVE );
				$res = $dbr->select( 
					'page', 
					'page_title', 
					'page_namespace = 0', 
					__METHOD__, 
					array( 'ORDER BY' => 'length(page_title) DESC' ));

				// Iterate through the page titles
				$new_text = $text;
				foreach( $res as $row ) {
					// Page titles are stored in the database with spaces
					// replaced by underscores. Therefore we now convert
					// the underscores back to spaces.
					$title = str_replace('_', ' ', $row->page_title);

					if ( $title != $my_title ) {
						// split the string by [[...]] groups
						$arr = preg_split( '/(\[\[.*?\]\])/', $new_text, -1, PREG_SPLIT_DELIM_CAPTURE );
						$safe_title = str_replace( '/', '\/', $title );
						for ( $i = 0; $i < count( $arr ); $i+=2 ) {
							// even indexes will text that is not enclosed by brackets
							$arr[$i] = preg_replace( '/\b(' . $safe_title . ')\b/i', '[[$1]]', $arr[$i] );
						};
						dump( $arr );
						$new_text = implode( '', $arr );
					}; // if $title != $my_title
				}; // foreach $res as $row
				if ( $new_text != '' ) {
					$text = $new_text;
				};
			};
			return true;
		}

		/*
		 * The following function was initially used, but it does not replace
		 * every occurrence of the title words in the page text.
		 *
		public static function parse1( &$new_text ) {
			// Now look for every occurrence of $title in the
			// page $text and enclose it in double square brackets,
			// unless it is already enclosed in brackets (directly
			// adjacent or remotely, see http://stackoverflow.com/questions/10672286
			// Regex built with the help from Eugene @ Stackoverflow
			// http://stackoverflow.com/a/10672440/270712
			$new_text = preg_replace(
				'/(\b' . str_replace('/', '\/', $title) . '\b)([^\]]+(\[|$))/ium',
				'[[$1]]$2',
				$new_text );
			return true;
			}
		*/
	}
	// vim: ts=2:sw=2:noet
