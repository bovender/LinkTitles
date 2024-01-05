<?php

/**
 * The LinkTitles\Targets class.
 *
 * Copyright 2012-2024 Daniel Kraus <bovender@bovender.de> ('bovender')
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
 * Fetches potential target page titles from the database.
 */
class Targets {
	private static $instance;

	/**
	 * Singleton factory that returns a (cached) database query results with
	 * potential target page titles.
	 *
	 * The subset of pages that may serve as target pages depends on the namespace
	 * of the source page. Therefore, if the $sourceNamespace differs from the
	 * cached namespace, the database is queried again.
	 *
	 * @param  String $sourceNamespace The namespace of the current page.
	 * @param  Config $config    LinkTitles configuration.
	 */
	public static function singleton( \Title $title, Config $config ) {
		if ( ( self::$instance === null ) || ( self::$instance->sourceNamespace != $title->getNamespace() ) ) {
			self::$instance = new Targets( $title, $config );
		}
		return self::$instance;
	}

	/**
	 * Invalidates the cache; the next call of Targets::singleton() will trigger
	 * a database query.
	 *
	 * Use this in unit tests which are performed in a single request cycle so that
	 * changes to the pages list may not be picked up by the cached Targets instance.
	 */
	public static function invalidate() {
		self::$instance = null;
	}

	/**
	 * Holds the results of a database query for target page titles, filtered
	 * and sorted.
	 * @var IResultWrapper $queryResult
	 */
	public $queryResult;

	/**
	 * Holds the source page's namespace (integer) for which the list of target
	 * pages was built.
	 * @var Int $sourceNamespace
	 */
	public $sourceNamespace;

	private $config;

	/**
	 * Stores the CHAR_LENGTH function to be used with the database connection.
	 * @var string $charLengthFunction
	 */
	private $charLengthFunction;

	/**
	 * The constructor is private to enforce using the singleton pattern.
	 * @param  \Title $title
	 */
	private function __construct( \Title $title, Config $config) {
		$this->config = $config;
		$this->sourceNamespace = $title->getNamespace();
		$this->fetch();
	}

	//
	/**
	 * Fetches the page titles from the database.
	 */
	private function fetch() {
		( $this->config->preferShortTitles ) ? $sortOrder = 'ASC' : $sortOrder = 'DESC';

		// Build a blacklist of pages that are not supposed to be link
		// targets. This includes the current page.
		if ( $this->config->blackList ) {
			$blackList = 'page_title NOT IN ' .
				str_replace( ' ', '_', '("' . implode( '","', str_replace( '"', '\"', $this->config->blackList ) ) . '")' );
		} else {
			$blackList = null;
		}

		if ( $this->config->sameNamespace ) {
			// Build our weight list. Make sure current namespace is first element
			$namespaces = array_diff( $this->config->targetNamespaces, [ $this->sourceNamespace ] );
			array_unshift( $namespaces, $this->sourceNamespace );
		} else {
			$namespaces = $this->config->targetNamespaces;
		}

		if ( !$namespaces) {
			// If there are absolutely no target namespaces (not even the one of the
			// source page), we can just return.
			return;
		}

		// No need for sanitiy check. we are sure that we have at least one element in the array
		$weightSelect = "CASE page_namespace ";
		$currentWeight = 0;
		foreach ($namespaces as &$namespaceValue) {
				$currentWeight = $currentWeight + 100;
				$weightSelect = $weightSelect . " WHEN " . $namespaceValue . " THEN " . $currentWeight . PHP_EOL;
		}
		$weightSelect = $weightSelect . " END ";
		$namespacesClause = '(' . implode( ', ', $namespaces ) . ')';

		// Build an SQL query and fetch all page titles ordered by length from
		// shortest to longest. Only titles from 'normal' pages (namespace uid
		// = 0) are returned. Since the db may be sqlite, we need a try..catch
		// structure because sqlite does not support the CHAR_LENGTH function.
		$dbr = wfGetDB( DB_REPLICA );
		$this->queryResult = $dbr->select(
			'page',
			array( 'page_title', 'page_namespace' , "weight" => $weightSelect),
			array_filter(
				array(
					'page_namespace IN ' . $namespacesClause,
					$this->charLength() . '(page_title) >= ' . $this->config->minimumTitleLength,
					$blackList,
				)
			),
			__METHOD__,
			array( 'ORDER BY' => 'weight ASC, ' . $this->charLength() . '(page_title) ' . $sortOrder )
		);
	}

	private function charLength() {
		if ($this->charLengthFunction === null) {
			$this->charLengthFunction = $this->config->sqliteDatabase() ? 'LENGTH' : 'CHAR_LENGTH';
		}
		return $this->charLengthFunction;
	}
}
