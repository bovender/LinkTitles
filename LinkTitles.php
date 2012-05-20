<?php
/*
 *      \file LinkTitles.php
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

  $wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'LinkTitles',
    'author'         => '[http://www.mediawiki.org/wiki/User:Bovender Daniel Kraus]', 
    'url'            => 'http://www.mediawiki.org/wiki/Extension:LinkTitles',
    'version'        => '0.0.1',
    'descriptionmsg' => 'linktitles-desc'
    );

  $wgExtensionMessagesFiles['LinkTitles'] = dirname( __FILE__ ) . '/LinkTitles.i18n.php';

  $wgAutoloadClasses['LinkTitles'] = dirname(__FILE__) . '/LinkTitles.body.php';
  $wgAutoloadClasses['LinkTitlesFetcher'] = dirname(__FILE__) . '/LinkTitlesFetcher.body.php';

  // Define a setup function
  $wgHooks['ParserFirstCallInit'][]       = 'LinkTitles::Setup';
	$wgHooks['ArticleSave'][]               = 'LinkTitles::onArticleSave';
		
	// error_reporting(E_ALL);
	// ini_set('display_errors', 'Off');
	// ini_set('error_log', 'php://stderr');

	// vim: ts=2:sw=2:noet

