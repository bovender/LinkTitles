<?php

/**
 * The LinkTitles\Config class holds configuration for the LinkTitles extension.
 *
 * Copyright 2012-2024 Daniel Kraus <bovender@bovender.de> ('bovender')
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
 * Holds LinkTitles configuration.
 *
 * This class encapsulates the global configuration variables so we do not have
 * to pull those globals into scope in the individual LinkTitles classes.
 *
 * Using a dedicated configuration class also facilitates overriding certain
 * options, i.e. in a maintenance script that is invoked with flags from the
 * command line.
 *
 * @since 5.0.0
 */
class Config {
	/**
	 * Whether to add links to a page when the page is edited/saved.
	 * @var bool $parseOnEdit
	 */
	public $parseOnEdit;

	/**
	 * Whether to add links to a page when the page is rendered.
	 * @var bool $parseOnRender
	 */
	public $parseOnRender;

	/**
	 * Indicates whether to prioritize short over long titles.
	 * @var bool $preferShortTitles
	 */
	public $preferShortTitles;

	/**
	 * Minimum length of a page title for it to qualify as a potential link target.
	 * @var int $minimumTitleLength
	 */
	public $minimumTitleLength;

	/**
	 * Array of page titles that must never be link targets.
	 *
	 * This may be useful to exclude common abbreviations or acronyms from
	 * automatic linking.
	 * @var Array $blackList
	 */
	public $blackList;

	/**
	 * Array of those namespaces (integer constants) whose pages may be linked
	 * when edited.
	 * @var Array $sourceNamespaces
	 */
	public $sourceNamespaces;

	/**
	 * Array of those namespaces (integer constants) whose pages may be linked
	 * to a source page.
	 * @var Array $targetNamespaces
	 */
	public $targetNamespaces;

	/**
	 * Indicates whether to add a link to the first occurrence of a page title
	 * only (true), or add links to all occurrences on the source page (false).
	 * @var bool $firstOnly;
	 */
	public $firstOnly;

	/**
	 * Indicates whether to operate in smart mode, i.e. link to pages even if the
	 * case does not match. Without smart mode, pages are linked to only if the
	 * exact title appears on the source page.
	 * @var bool $smartMode;
	 */
	public $smartMode;

	/**
	 * Mirrors the global MediaWiki variable $wgCapitalLinks that indicates
	 * whether or not page titles are fully case sensitive
	 * @var bool $capitalLinks;
	 */
	public $capitalLinks;

	/**
	 * Whether or not to link to pages only if the page title appears at the
	 * start of a word on the target page (i.e., link 'MediaWiki' to a page
	 * 'Media', but not to a page 'Wiki').
	 *
	 * Set both $wordStartOnly and $wordEndOnly to true to enforce matching
	 * whole titles.
	 *
	 * @var bool $wordStartOnly;
	 */
	public $wordStartOnly;

	/**
	 * Whether or not to link to pages only if the page title appears at the
	 * end of a word on the target page (i.e., link 'MediaWiki' to a page
	 * 'Wiki', but not to a page 'Media').
	 *
	 * Set both $wordStartOnly and $wordEndOnly to true to enforce matching
	 * whole titles.
	 *
	 * @var bool $wordEndOnly;
	 */
	public $wordEndOnly;

	/**
	 * Whether or not to skip templates. If set to true, text inside transclusions
	 * will not be linked.
	 * @var bool $skipTemplates
	 */
	public $skipTemplates;

	/**
	 * Whether or not to parse headings.
	 * @var bool $parseHeadings
	 */
	public $parseHeadings;

	/**
	 * Whether to check if a potential target page links back to the source page.
	 * Set this to true to avoid indirect linkbacks.
	 *
	 * @var bool $checkRedirect
	 */
	public $checkRedirect;

	/**
	 * Whether to enable the __NOAUTOLINKTARGET__ magic word which prevents
	 * a potential target page from being linked to.
	 *
	 * @var bool $enableNoTargetMagicWord
	 */
	public $enableNoTargetMagicWord;

	/**
	 * Time (in seconds) after which to reload the special page.
	 * @var integer reload interval (in seconds)
	 */
	public $specialPageReloadAfter;

	/**
	 * Whether to link to pages in the same namespace (default is true).
	 * @var bool $sameNamespace;
	 */
	public $sameNamespace;

	/**
	 * Caches the global $wgDBtype variable.
	 * @var string $dbType;
	 */
	private $dbType;

	public $enableConsoleOutput;
	public $enableDebugConsoleOutput;

	/**
	 * Determines whether the MediaWiki database is SQLITE by inspecting the
	 * $wgDBtype variable (which is cached in $this->dbType).
	 * @return bool True if the database is SQLITE.
	 */
	public function sqliteDatabase() {
		return $this->dbType === 'sqlite';
	}

	/**
	 * Constructs a new Config object.
	 *
	 * The object's member variables will automatically be set with the values
	 * from the corresponding global variables.
	 */
	public function __construct() {
		global $wgLinkTitlesParseOnEdit;
		global $wgLinkTitlesParseOnRender;
		global $wgLinkTitlesPreferShortTitles;
		global $wgLinkTitlesMinimumTitleLength;
		global $wgLinkTitlesBlackList;
		global $wgLinkTitlesSourceNamespaces;
		global $wgLinkTitlesTargetNamespaces;
		global $wgLinkTitlesSameNamespace;
		global $wgLinkTitlesFirstOnly;
		global $wgLinkTitlesSmartMode;
		global $wgCapitalLinks;
		global $wgLinkTitlesWordStartOnly;
		global $wgLinkTitlesWordEndOnly;
		global $wgLinkTitlesSkipTemplates;
		global $wgLinkTitlesParseHeadings;
		global $wgLinkTitlesEnableNoTargetMagicWord;
		global $wgLinkTitlesCheckRedirect;
		global $wgLinkTitlesSpecialPageReloadAfter;
		global $wgDBtype;
		$this->parseOnEdit = $wgLinkTitlesParseOnEdit;
		$this->parseOnRender = $wgLinkTitlesParseOnRender;
		$this->preferShortTitles = $wgLinkTitlesPreferShortTitles;
		$this->minimumTitleLength = $wgLinkTitlesMinimumTitleLength;
		$this->blackList = $wgLinkTitlesBlackList;
		$this->sourceNamespaces = $wgLinkTitlesSourceNamespaces ? $wgLinkTitlesSourceNamespaces : [ NS_MAIN ];
		$this->targetNamespaces = $wgLinkTitlesTargetNamespaces;
		$this->sameNamespace = $wgLinkTitlesSameNamespace;
		$this->firstOnly = $wgLinkTitlesFirstOnly;
		$this->smartMode = $wgLinkTitlesSmartMode;
		$this->capitalLinks = $wgCapitalLinks; // MediaWiki global variable
		$this->wordStartOnly = $wgLinkTitlesWordStartOnly;
		$this->wordEndOnly = $wgLinkTitlesWordEndOnly;
		$this->skipTemplates = $wgLinkTitlesSkipTemplates;
		$this->parseHeadings = $wgLinkTitlesParseHeadings;
		$this->enableNoTargetMagicWord = $wgLinkTitlesEnableNoTargetMagicWord;;
		$this->checkRedirect = $wgLinkTitlesCheckRedirect;;
		$this->specialPageReloadAfter = $wgLinkTitlesSpecialPageReloadAfter;
		$this->enableConsoleOutput = false;
		$this->enableDebugConsoleOutput = false;
		$this->dbType = $wgDBtype;
	}
}
