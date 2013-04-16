<?php
/*
 *      \file LinkTitles.php
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

	/*
		error_reporting(E_ALL);
		ini_set('display_errors', 'On');
		ini_set('error_log', 'php://stderr');
		$wgMainCacheType = CACHE_NONE;
		$wgCacheDirectory = false;
	*/

	// Configuration variables
	$wgLinkTitlesPreferShortTitles = false;	
	$wgLinkTitlesMinimumTitleLength = 3;
	$wgLinkTitlesParseHeadings = false;
	$wgLinkTitlesParseOnEdit = true;
	$wgLinkTitlesParseOnRender = false;
	$wgLinkTitlesSkipTemplates = false;
	$wgLinkTitlesBlackList = array();
	$wgLinkTitlesFirstOnly = false;
	$wgLinkTitlesWordStartOnly = true;
	$wgLinkTitlesWordEndOnly = true;
	$wgLinkTitlesSmartMode = true;

  $wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'LinkTitles',
    'author'         => '[https://www.mediawiki.org/wiki/User:Bovender Daniel Kraus]', 
    'url'            => 'https://www.mediawiki.org/wiki/Extension:LinkTitles',
    'version'        => '2.2.0',
    'descriptionmsg' => 'linktitles-desc'
    );

  $wgExtensionMessagesFiles['LinkTitles'] = dirname( __FILE__ ) . '/LinkTitles.i18n.php';
  $wgExtensionMessagesFiles['LinkTitlesMagic'] = dirname( __FILE__ ) . '/LinkTitles.i18n.magic.php';
  $wgAutoloadClasses['LinkTitles'] = dirname( __FILE__ ) . '/LinkTitles.body.php';
	$wgExtensionFunctions[] = 'LinkTitles::setup';

	// vim: ts=2:sw=2:noet

