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
		/// Default setup function.
		public static function Setup( &$parser ) {
			return true;
		}

		/// This function is hooked to the ArticleSave event.
		/// It will be called whenever a page is about to be 
		/// saved.
		public static function onArticleSave( &$article, &$user, &$text, &$summary,
				$minor, $watchthis, $sectionanchor, &$flags, &$status ) {
			error_reporting(E_ALL);
			ini_set('display_errors', 'Off');
			ini_set('error_log', 'php://stderr');
			global $wgRequest;
			$params = new DerivativeRequest( 
				$wgRequest, 
				array(
					'action' => 'query',
					'list' => 'allpages')
			);
			// $api = new ApiMain( $params, false ); // false: do not edit page
			// $api->execute();
			// $data = & $api->getResultData();
			// dump($data);
			return true;
		}

	}
	// vim: ts=2:sw=2:noet
