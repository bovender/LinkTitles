<?php

/**
 * The Splitter class caches a regular expression that delimits text to be parsed.
 *
 * Copyright 2012-2021 Daniel Kraus <bovender@bovender.de> ('bovender')
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
class Splitter {
	/**
	 * The splitting expression that separates text to be parsed from text that
	 * must not be parsed.
	 * @var String $splitter
	 */
	public $splitter;

	/**
	 * The LinkTitles configuration for this Splitter instance.
	 * @var Config $config
	 */
	public $config;

	private static $instance;

	/**
	 * Gets the Splitter singleton; may build one with the given config or the
	 * default config if none is given.
	 *
	 * If the instance was already created, it does not matter what Config this
	 * method is called with. To re-create an instance with a different Config,
	 * call Splitter::invalidate() first.
	 *
	 * @param  Config|null $config LinkTitles configuration.
	 */
	public static function singleton( Config &$config = null ) {
		if ( self::$instance === null ) {
			if ( $config === null ) {
				$config = new Config();
			}
			self::$instance = new Splitter( $config );
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
		$this->buildSplitter();
	}

	/**
	 * Splits a text into sections that may be linked and sections that may not
	 * be linked (e.g., because they already are a link, or a template, etc.).
	 *
	 * @param  String &$text Text to split.
	 * @return Array of strings where even indexes point to linkable sections.
	 */
	public function split( &$text ) {
		return preg_split( $this->splitter, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
	}

	/*
	 * Builds the delimiter that is used in a regexp to separate
	 * text that should be parsed from text that should not be
	 * parsed (e.g. inside existing links etc.)
	 */
	private function buildSplitter() {
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

		// Match WikiText headings.
		// Since there is a user option to skip headings, we make this part of the
		// expression optional. Note that in order to use preg_split(), it is
		// important to have only one capturing subpattern (which precludes the use
		// of conditional subpatterns).
		// Caveat: This regex pattern should be improved to deal with balanced '='s
		// only. However, this would require grouping in the pattern which does not
		// agree with preg_split.
		$headingsDelimiter = $this->config->parseHeadings ? '' : '^=+[^=]+=+$|';

		$urlPattern = '[a-z]+?\:\/\/(?:\S+\.)+\S+(?:\/.*)?';
		$this->splitter = '/(' .                     // exclude from linking:
			'\[\[.*?\]\]|' .                            // links
			$headingsDelimiter .                        // headings (if requested)
			$templatesDelimiter .                       // templates (if requested)
			'^ .+?\n|\n .+?\n|\n .+?$|^ .+?$|' .        // preformatted text
			'<nowiki>.*?<.nowiki>|<code>.*?<\/code>|' . // nowiki/code
			'<pre>.*?<\/pre>|<html>.*?<\/html>|' .      // pre/html
			'<script>.*?<\/script>|' .                  // script
			'<syntaxhighlight.*?>.*?<\/syntaxhighlight>|' .                  // syntaxhighlight
			'<gallery>.*?<\/gallery>|' .                // gallery
			'<div.*?>|<\/div>|' .                       // attributes of div elements
			'<input.+<\/input>|' .                      // input tags and anything between them
			'<select.+<\/select>|' .                    // select tags and anything between them
			'<span.*?>|<\/span>|' .                     // attributes of span elements
			'<file>[^<]*<\/file>|' .                    // stuff inside file elements
			'style=".+?"|class=".+?"|data-sort-value=".+?"|' . 	// styles and classes (e.g. of wikitables)
			'<noautolinks>.*?<\/noautolinks>|' .        // custom tag 'noautolinks'
			'\[' . $urlPattern . '\s.+?\]|'. $urlPattern .  '(?=\s|$)|' . // urls
			'(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b)' .        // email addresses
			')/ismS';
	}
}
