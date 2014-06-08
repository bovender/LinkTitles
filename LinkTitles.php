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
	/// This file will be loaded by MediaWiki if the extension is included in 
	/// `LocalSettings.php`. It sets up the classes for auto-loading, 
	/// announces metadata, and defines a number of @link config configuration 
	/// variables @endlink.

	/// @cond
  if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
  }
	/// @endcond

	/*
		error_reporting(E_ALL);
		ini_set('display_errors', 'On');
		ini_set('error_log', 'php://stderr');
		$wgMainCacheType = CACHE_NONE;
		$wgCacheDirectory = false;
	*/

	/// @defgroup config Configuration
  /// The global configuration variables can be overriden in the local 
	/// `LocalSettings.php` file just like with any other extension.
	/// These variables are all defined in LinkTitles.php.

	/// Controls precedence of page titles. If true, pages with shorter titles 
	/// are given preference over pages with longer titles (e.g., link to 
	/// 'Media' rather than 'MediaWiki'). If false (default), longer titles 
	/// (which tend to be more specific) are given precedence (e.g., link to 
	/// 'MediaWiki' rather than 'Media' if both pages exist).
	/// @ingroup config
	$wgLinkTitlesPreferShortTitles = false;	

	/// The minimum number of characters in a title that is required for it to 
	/// be automatically linked to.
	/// @ingroup config
	$wgLinkTitlesMinimumTitleLength = 3;

	/// Determines whether or not to insert links into headings.
	/// @ingroup config
	$wgLinkTitlesParseHeadings = false;

	/// Important configuration variable that determines when the extension 
	/// will process a page. If set to true in `LocalSettings.php`, links will 
	/// be inserted whenever a page is edited and saved (unless 'minor 
	/// modifications' is checked). If set to false, the extension will not do 
	/// anything when a page is edited 
	/// and saved.
	/// @ingroup config
	$wgLinkTitlesParseOnEdit = true;

	/// Less important configuration variable that determines when the 
	/// extension will process a page. If set to true in LocalSettings.php, 
	/// links will be inserted when a page is rendered for viewing.
	/// @note Whether a page will be rendered or just fetched from the page 
	/// cache is unpredictable. Therefore, pages may not always be parsed for 
	/// possible links when this variable is set to true.
	/// @warning Setting this to true has the potential to affect lots of page 
	/// views (but see note regarding cached pages).
	/// @ingroup config
	$wgLinkTitlesParseOnRender = false;

	/// Determines whether to parse text inside templates. If this is set to 
	/// true in LocalSettings.php, 
	/// @ingroup config
	$wgLinkTitlesSkipTemplates = false;

	/// Blacklist of page titles that should never be linked. 
	/// @ingroup config
	$wgLinkTitlesBlackList = array();

	/// Determines whether to link only the first occurrence of a page 
	/// title on a page or all occurrences. Default is false: All occurrences 
	/// on a page are linked.
	/// @ingroup config

	$wgLinkTitlesFirstOnly = false;

	/// Determines whether a page title must occur at the start of a word in 
	/// order for it to be linked. Example: Given a page 'Media' and an 
	/// article that contains the word 'Multimedia', the default setting 
	/// (`true`) will prevent the page from being linked. If this setting is 
	/// overriden in `LocalSettings.php` to be `false`, a link would be 
	/// inserted: `Multi[[media]]`.
	/// @note If both $wgLinkTitlesWordStartOnly and $wgLinkTitlesWordEndOnly 
	/// are overriden to `false` in `LocalSettings.php`, you may get weird 
	/// linking. As a (contrieved) example, consider a wiki that has a page 
	/// "spa", then the word "namespace" in a technical article would be 
	/// linked as `name[[spa]]ce`, which is likely not what you want.
	/// @ingroup config
	$wgLinkTitlesWordStartOnly = true;

	/// Determines whether a page title must end with the end of a word in 
	/// order for it to be linked. Example: Given a page 'Media' and an 
	/// article that contains the word 'MediaWiki', the default setting 
	/// (`true`) will prevent the page from being linked, because both words 
	/// have different endings. If this setting is overriden in 
	/// `LocalSettings.php` to be `false`, a link would be inserted: 
	/// `[[Media]]wiki`.
	/// @note Setting this to false may have undesired effects because there 
	/// are many short words that may randomly occur in longer words, but are 
	/// semantically unrelated.
	/// @note If both $wgLinkTitlesWordStartOnly and $wgLinkTitlesWordEndOnly 
	/// are overriden to `false` in `LocalSettings.php`, you may get weird 
	/// linking. As a (contrieved) example, consider a wiki that has a page 
	/// "spa", then the word "namespace" in a technical article would be 
	/// linked as `name[[spa]]ce`, which is likely not what you want.
	/// @ingroup config
	$wgLinkTitlesWordEndOnly = true;

	/// Important setting that controls the extension's smart mode of 
	/// operation. With smart mode turned on (default), the extension will 
	/// first link all occurrences of a page title on a page in a 
	/// case-sensitive manner (but see note). It will then perform a second 
	/// pass in a case-__in__sensitive manner. For example, if you have a page 
	/// called "IgAN" (abbreviation for IgA nephritis, a kidney disease) and 
	/// someone writes the alternative form "Igan" in their article, then the 
	/// page would not be linked if smart mode is turned off.
	/// @note Because smart mode constitutes two-pass processing of a page, 
	/// rather than single-pass, the processing time will increase. This may 
	/// not be noticeable on single page saves, but may play a role during 
	/// @link batch batch processing @endlink.
	/// @ingroup config
	$wgLinkTitlesSmartMode = true;

	/// Time limit for online batch processing. This determines the maximum 
	/// amount of time in seconds that page processing will take before a 
	/// refresh of the special page is issued.
	/// @ingroup config
	$wgLinkTitlesTimeLimit = 0.2;

	/// @cond
  $wgExtensionCredits['parserhook'][] = array(
    'path'           => __FILE__,
    'name'           => 'LinkTitles',
    'author'         => '[https://www.mediawiki.org/wiki/User:Bovender Daniel Kraus]', 
    'url'            => 'https://www.mediawiki.org/wiki/Extension:LinkTitles',
    'version'        => '2.4.2',
    'descriptionmsg' => 'linktitles-desc'
    );

  $wgExtensionMessagesFiles['LinkTitles'] = dirname( __FILE__ ) . '/LinkTitles.i18n.php';
  $wgExtensionMessagesFiles['LinkTitlesMagic'] = dirname( __FILE__ ) . '/LinkTitles.i18n.magic.php';
  $wgAutoloadClasses['LinkTitles'] = dirname( __FILE__ ) . '/LinkTitles.body.php';
  $wgAutoloadClasses['SpecialLinkTitles'] = dirname( __FILE__ ) . '/SpecialLinkTitles.php';
	$wgExtensionFunctions[] = 'LinkTitles::setup';

	// Settings for the batch-processing special page
	$wgSpecialPages['LinkTitles'] = 'SpecialLinkTitles';
	$wgSpecialPageGroups['LinkTitles'] = 'other';
	/// @endcond
	
	/// The @link SpecialLinkTitles special page @endlink provides a distinct 
	/// right `linktitles-batch`. A user must be granted this right in order 
	/// to be able to visit this page.
	$wgAvailableRights[] = 'linktitles-batch';

	/// Grants the `linktitles-batch` right to sysops by default.
	/// Override this only with care. If anybody can execute the
	/// @link SpecialLinkTitles special page @endlink the server might 
	/// experience high loads.
	/// @ingroup config
	$wgGroupPermissions['sysop']['linktitles-batch'] = true;

// vim: ts=2:sw=2:noet:comments^=\:///

