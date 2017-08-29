<?php
/**
 * The LinkTitles\Target represents a Wiki page that is a potential link target.
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
 * Represents a page that is a potential link target.
 */
class Target {
	/**
	 * A Title object for the target page currently being examined.
	 * @var \Title $title
	 */
	private $title;

	/**
	 * Caches the target page content as a \Content object.
	 *
	 * @var \Content $content
	 */
	private $content;

	/**
	 * Regex that matches the start of a word; this expression depends on the
	 * setting of LinkTitles\Config->wordStartOnly;
	 * @var String $wordStart
	 */
	public $wordStart;

	/**
	 * Regex that matches the end of a word; this expression depends on the
	 * setting of LinkTitles\Config->wordEndOnly;
	 * @var String $wordEnd
	 */
	public $wordEnd;

	/**
	 * LinkTitles configuration.
	 * @var Config $config
	 */
	private $config;

	private $caseSensitiveLinkValueRegex;

	/**
	 * Constructs a new Target object
	 *
	 * The parameters may be taken from database rows, for example.
	 *
	 * @param Int $nameSpace Name space of the target page
	 * @param String &$title Title of the target page
	 */
	public function __construct( $nameSpace, $title, Config &$config ) {
		$this->title = \Title::makeTitleSafe( $nameSpace, $title );
		$this->titleValue = $this->title->getTitleValue();
		$this->config = $config;

		// Use unicode character properties rather than \b escape sequences
		// to detect whole words containing non-ASCII characters as well.
		// Note that this requires a PCRE library that was compiled with
		// --enable-unicode-properties
		( $config->wordStartOnly ) ? $this->wordStart = '(?<!\pL)' : $this->wordStart = '';
		( $config->wordEndOnly ) ? $this->wordEnd = '(?!\pL)' : $this->wordEnd = '';
	}

	/**
	 * Gets the string representation of the target title.
	 * @return String title text
	 */
	public function getTitleText() {
		return $this->title->getText();
	}

	/**
	 * Gets the title string with certain characters escaped that may interfere
	 * with regular expressions.
	 * @return String representation of the title, regex-safe
	 */
	public function getRegexSafeTitle() {
		return preg_quote( $this->title->getText(), '/' );
	}

	/**
	 * Builds a regular expression of the title
	 * @return String regular expression for this title.
	 */
	public function getCaseSensitiveRegex() {
		return $this->buildRegex( $this->getCaseSensitiveLinkValueRegex() );
	}

	/**
	 * Builds a regular expression pattern for the title in a case-insensitive
	 * way.
	 * @return String case-insensitive regular expression pattern for the title
	 */
	public function getCaseInsensitiveRegex() {
		return $this->buildRegex( $this->getRegexSafeTitle() ) . 'i';
	}

	/**
	 * Builds the basic regex that is used to match target page titles in a source
	 * text.
	 * @param  String $searchTerm Target page title (special characters must be quoted)
	 * @return String regular expression pattern
	 */
	private function buildRegex( $searchTerm ) {
		return '/(?<![\:\.\@\/\?\&])' . $this->wordStart . $searchTerm . $this->wordEnd . '/S';
	}

	/**
	 * Gets the (cached) regex for the link value.
	 *
	 * Depending on the $config->capitalLinks setting, the title has to be
	 * searched for either in a strictly case-sensitive way, or in a 'fuzzy' way
	 * where the first letter of the title may be either case.
	 *
	 * @return String regular expression pattern for the link value.
	 */
	public function getCaseSensitiveLinkValueRegex() {
		if ( $this->caseSensitiveLinkValueRegex === null ) {
			$regexSafeTitle = $this->getRegexSafeTitle();
			if ( $this->config->capitalLinks && ( $regexSafeTitle[0] != '\\' )) {
				$this->caseSensitiveLinkValueRegex = '((?i)' . $regexSafeTitle[0] . '(?-i)' . substr($regexSafeTitle, 1) . ')';
			}	else {
				$this->caseSensitiveLinkValueRegex = '(' . $regexSafeTitle . ')';
			}
		}
		return $this->caseSensitiveLinkValueRegex;
	}

	/**
	 * Returns the \Content of the target page.
	 *
	 * The value is cached.
	 * @return \Content Content of the Target page.
	 */
	public function getContent() {
		if ( $this->content === null ) {
			$this->content = \WikiPage::factory( $this->title )->getContent();
		};
		return $this->content;
	}

	/**
	 * Examines the current target page. Returns true if it may be linked;
	 * false if not. This depends on two settings:
	 * $wgLinkTitlesCheckRedirect and $wgLinkTitlesEnableNoTargetMagicWord
	 * and whether the target page is a redirect or contains the
	 * __NOAUTOLINKTARGET__ magic word.
	 *
	 * @param \Title $fromTitle
	 *
	 * @return boolean
	 */
	public function mayLinkTo( \Title $fromTitle ) {
		// If checking for redirects is enabled and the target page does
		// indeed redirect to the current page, return the page title as-is
		// (unlinked).
		if ( $this->config->checkRedirect ) {
			$redirectTitle = $this->getContent()->getUltimateRedirectTarget();
			if ( $redirectTitle && $redirectTitle->equals( $fromTitle ) ) {
				return false;
			}
		};
		// If the magic word __NOAUTOLINKTARGET__ is enabled and the target
		// page does indeed contain this magic word, return the page title
		// as-is (unlinked).
		if ( $this->config->enableNoTargetMagicWord ) {
			if ( $this->getContent()->matchMagicWord( \MagicWord::get('MAG_LINKTITLES_NOTARGET') ) ) {
				return false;
			}
		};
		return true;
	}

	/**
	 * Determines if the Target's title is the same as another title.
	 * @param  Title   $otherTitle Other title
	 * @return boolean             True if the $otherTitle is the same, false if not.
	 */
	public function isSameTitle( \Title $otherTitle) {
		return $this->title->equals( $otherTitle );
	}
}
