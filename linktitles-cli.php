#!/usr/bin/env php
<?php

/**
 * LinkTitles command line interface (CLI)/maintenance script
 *
 *  Copyright 2012-2024 Daniel Kraus <bovender@bovender.de> @bovender
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *  MA 02110-1301, USA.
 */
namespace LinkTitles;

// Attempt to include the maintenance base class from:
//   $wgScriptPath/maintenance/Maintenance.php
// Our script is normally located at:
//   $wgScriptPath/extensions/LinkTitles/LinkTitles_Maintenance.php
$maintenanceScript = __DIR__ . "/../../maintenance/Maintenance.php";
if ( file_exists( $maintenanceScript ) ) {
	require_once $maintenanceScript;
}
else
{
	// Did not find the script where we expected it (maybe because we are a
	// symlinked file -- __DIR resolves symbolic links).
	$maintenanceScript = __DIR__ . "/Maintenance.php";
	if ( file_exists( $maintenanceScript ) ) {
		require_once $maintenanceScript;
	}
	else
	{
		die("FATAL: Could not locate Maintenance.php.\n" .
			"You may want to create a symbolic link named Maintenance.php in this directory\n" .
			"which points to <YOUR_MEDIAWIKI_ROOT_IN_FILESYSTEM>/extensions/Maintenance.php\n" .
			"Ex.: ln -s /var/www/wiki/maintenance/Maintenance.php\n\n");
	}
};

require_once( __DIR__ . "/includes/Extension.php" );

/**
 * Core class of the maintanance script.
 * @note Note that the execution of maintenance scripts is prohibited for
 * an Apache web server due to a `.htaccess` file that declares `deny from
 * all`. Other webservers may exhibit different behavior. Be aware that
 * anybody who is able to execute this script may place a high load on the
 * server.
 * @ingroup batch
 */
class Cli extends \Maintenance {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription("Iterates over wiki pages and automatically adds links to other pages.");
		$this->addOption(
			"start",
			"Set start index.",
			false, // not required
			true,  // requires argument
			"s"
		);
		$this->addOption(
			"limit",
			"Limit the number of pages to be replaced.",
			false, // not required
			true,  // requires argument
			"l"
		);
		$this->addOption(
			"min_lenght",
			"Overides the \$wgLinkTitlesMinimumTitleLength variable",
			false, // not required
			true,  // requires argument
			"l"
		);
		$this->addOption(
			"max_length",
			"Overides the \$wgLinkTitlesMaximumTitleLength variable",
			false, // not required
			true,  // requires argument
			"l"
		);		
		$this->addOption(
			"page",
			"page name to process",
			false, // not required
			true,  // requires argument
			"p"
		);
		$this->addOption(
			"target",
			"Specify a target page name to link to.",
			false, // not required
			true,  // requires argument
			"t"
		);		
		$this->addOption(
			"verbose",
			"print detailed progress information",
			false, // not required
			false, // does not require an argument
			"v"
		);
		$this->addOption(
			"dryrun",
			"Show pages that are going to be linked into, with the number of occurences",
			false, // not required
			false, // does not require an argument
			"d"
		);
		// TODO: Add back logging options.
		// TODO: Add configuration options.
		// $this->addOption(
		// 	"log",
		// 	"enables logging to console",
		// 	false, // not required
		// 	false,  // requires no argument
		// 	"l"
		// );
		// $this->addOption(
		// 	"debug",
		// 	"enables debug logging to console",
		// 	false, // not required
		// 	false  // requires no argument
		// );
	}

	/*
	 * Main function of the maintenance script.
	 * Will iterate over all pages in the wiki (starting at a certain index,
	 * if the `--start` option is given) and call LinkTitles::processPage() for
	 * each page.
	 */
	public function execute() {
		// if ($this->hasOption('log'))
		// {
		// 	Extension::$ltConsoleOutput = true;
		// }
		// if ($this->hasOption('debug'))
		// {
		// 	Extension::$ltConsoleOutputDebug = true;
		// }
		if ( $this->hasOption('page') ) {
			if ( !$this->hasOption( 'start' ) ) {
				$this->singlePage();
			}
			else {
				$this->error( 'FATAL: Must not use --start option with --page option.', 2 );
			}
		}
		else {
			$this->allPages( );
		}
	}

	/**
	 * Processes a single page.
	 * @return bool True on success, false on failure.
	 */
	private function singlePage() {
		$dryRun = $this->hasOption( 'dryrun' );

		$pageName = strval( $this->getOption( 'page' ) );
		$this->output( "Processing single page: '$pageName'\n" );
		$title = \Title::newFromText( $pageName );
		$success = Extension::processPage( $title, \RequestContext::getMain(), $dryRun );
		if ( $success ) {
			$this->output( "Finished.\n" );
		}
		else {
			$this->error( 'FATAL: There is no such page.', 3 );
		}

		$this->outputTargetPagesLog();

		return $success;
	}

	/**
	 * Process all pages in the Wiki.
	 * @return bool    True on success, false on failure.
	 */
	private function allPages( ) {
		$config = new Config();
		$verbose = $this->hasOption( 'verbose' );
		$dryRun = $this->hasOption( 'dryrun' );
		$targetPageName = strval( $this->getOption( 'target' ) );

		$startIndex = intval( $this->getOption( 'start', 0 ) );
		if ( $startIndex < 0 ) {
			$this->error( 'FATAL: Start index must be 0 or greater.', 1 );
		};
		
		$pageLimit = intval( $this->getOption( 'limit', 0 ) );
		if ( $pageLimit < 0 ) {
			$this->error( 'FATAL: limit must be 0 or greater.', 1 );
		};
		if (empty($pageLimit))
			$pageLimit = 999999999;

		$minLength = intval( $this->getOption( 'min_length', 0 ) );
		if (!empty($minLength))
			$GLOBALS['wgLinkTitlesMinimumTitleLength'] = $minLength;

		$maxLength = intval( $this->getOption( 'max_length', 0 ) );
		if (!empty($maxLength))
			$GLOBALS['wgLinkTitlesMaximumTitleLength'] = $maxLength;

		// Retrieve page names from the database.
		$dbr = $this->getDB( DB_REPLICA );
		$namespacesClause = str_replace( '_', ' ','(' . implode( ', ', $config->sourceNamespaces ) . ')' );
		$res = $dbr->select(
			'page',
			array( 'page_title', 'page_namespace' ),
			array(
				'page_namespace IN ' . $namespacesClause,
				'page_is_redirect = 0',
				"page_content_model = 'wikitext'",
			),
			__METHOD__,
			array(
				'ORDER BY' => 'page_namespace ASC, page_id DESC',
				'LIMIT' => $pageLimit,
				'OFFSET' => $startIndex
			)
		);

		$numPages = $res->numRows();
		$context = \RequestContext::getMain();
		$this->output( "Processing {$numPages} pages, starting at index {$startIndex}...\n" );

		$numProcessed = 0;
		foreach ( $res as $row ) {
			$title = \Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			$numProcessed += 1;
			$startIndex += 1;
			if ( $verbose ) {
				$this->output(
					sprintf(
						"%s - processed %5d of %5d (%2.0f%%) - index %5d - %s\r\n",
						date("c", time()),
						$numProcessed,
						$numPages,
						$numProcessed / $numPages * 100,
						$startIndex,
						$title
					)
				);
			} else {
				$this->output( sprintf( "\rPage #%d (%02.0f%%) ", $startIndex, $numProcessed / $numPages * 100 ) );
			}
			Extension::processPage( $title, $context, $dryRun, $targetPageName );
		}

		$this->output( "\rFinished.\n" );
		
		$this->outputTargetPagesLog();
	}

	private function outputTargetPagesLog() {
		$this->output( "\n\nList of titles that have been used as targets:\n\n" );
		$linkedPages = Targets::getTargetedPages();
		foreach ($linkedPages as $page => $pageCount)
			$this->output( "$page\t$pageCount\n" );
	}
}

$maintClass = 'LinkTitles\Cli';
if( defined('RUN_MAINTENANCE_IF_MAIN') ) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE );
}

// vim: ts=2:sw=2:noet:comments^=\:///
