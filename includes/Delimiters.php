<?php
/**
 * The Delimiters class caches a regular expression that delimits text to be parsed.
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
 * Caches a regular expression that delimits text to be parsed.
 */
class Delimiters {
	private static $instance;

	/**
	 * Singleton factory.
	 *
	 * @param  Config $config LinkTitles configuration.
	 */
	public static function default( Config $config ) {
		if ( self::$instance === null ) {
			self::$instance = new Delimiters( $config );
		}
		return self::$instance;
	}

	/**
	 * Invalidates the singleton instance.
	 *
	 * Used for unit testing.
	 */
	public static function invalidate() {
		self::$instance = null;
	}

	protected function __construct( Config $config) {
		$this->config = $config;
		$this->buildDelimiters();
	}

	/**
	 * The splitting expression that separates text to be parsed from text that
	 * must not be parsed.
	 * @var String $splitter
	 */
	public $splitter;

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

	private $config;

	/*
	 * Builds the delimiter that is used in a regexp to separate
	 * text that should be parsed from text that should not be
	 * parsed (e.g. inside existing links etc.)
	 */
	private function buildDelimiters() {
		// Use unicode character properties rather than \b escape sequences
		// to detect whole words containing non-ASCII characters as well.
		// Note that this requires a PCRE library that was compiled with
		// --enable-unicode-properties
		( $this->config->wordStartOnly ) ? $this->wordStart = '(?<!\pL)' : $this->wordStart = '';
		( $this->config->wordEndOnly ) ? $this->wordEnd = '(?!\pL)' : $this->wordEnd = '';

		if ( $this->config->skipTemplates )
		{
			// Use recursive regex to balance curly braces;
			// see http://www.regular-expressions.info/recurse.html
			$templatesDelimiter = '{{(?>[^{}]|(?R))*}}|';
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
		( $this->config->parseHeadings ) ? $delimiter = '' : $delimiter = '=+.+?=+|';
		$urlPattern = '[a-z]+?\:\/\/(?:\S+\.)+\S+(?:\/.*)?';
		$this->splitter = '/(' .                     // exclude from linking:
			'\[\[.*?\]\]|' .                            // links
			$delimiter .                                // titles (if requested)
			$templatesDelimiter .                       // templates (if requested)
			'^ .+?\n|\n .+?\n|\n .+?$|^ .+?$|' .        // preformatted text
			'<nowiki>.*?<.nowiki>|<code>.*?<\/code>|' . // nowiki/code
			'<pre>.*?<\/pre>|<html>.*?<\/html>|' .      // pre/html
			'<script>.*?<\/script>|' .                  // script
			'<gallery>.*?<\/gallery>|' .                // gallery
			'<div.+?>|<\/div>|' .                       // attributes of div elements
			'<span.+?>|<\/span>|' .                     // attributes of span elements
			'<file>[^<]*<\/file>|' .                    // stuff inside file elements
			'style=".+?"|class=".+?"|' .                // styles and classes (e.g. of wikitables)
			'<noautolinks>.*?<\/noautolinks>|' .        // custom tag 'noautolinks'
			'\[' . $urlPattern . '\s.+?\]|'. $urlPattern .  '(?=\s|$)|' . // urls
			'(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b)' .        // email addresses
			')/ismS';
	}
}
