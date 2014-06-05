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

// Attempt to include the maintenance base class from:
//   $wgScriptPath/maintenance/Maintenance.php
// Our script is normally located at:
//   $wgScriptPath/extensions/LinkTitles/LinkTitles.cli.php
$maintenanceScript = dirname( __FILE__ ) . "/../../maintenance/Maintenance.php";
if ( file_exists( $maintenanceScript ) ) {
	require_once $maintenanceScript;
}
else
{
	// Did not find the script where we expected it (maybe because we are a 
	// symlinked file -- __FILE__ resolves symbolic links).
	$maintenanceScript = dirname( __FILE__ ) . "/Maintenance.php";
	if ( file_exists( $maintenanceScript ) ) {
		require_once $maintenanceScript;
	}
	else
	{
		die("FATAL: Could not locate Maintenance.php.\n" .
			"You may want to create a symbolic link named Maintenance.php in this directory\n" .
			"which points to <YOUR_MEDIAWIKI_ROOT_IN_FILESYSTEM>/extensions/Maintenance.php\n" .
			"Ex.: ln -s /var/www/wiki/extensions/Maintenance.php\n\n");
	}
};

require_once( dirname( __FILE__ ) . "/LinkTitles.body.php" );
 
/// Core class of the maintanance script.
/// @note Note that the execution of maintenance scripts is prohibited for 
/// an Apache web server due to a `.htaccess` file that declares `deny from 
/// all`. Other webservers may exhibit different behavior. Be aware that 
/// anybody who is able to execute this script may place a high load on the 
/// server.
/// @ingroup batch
class LinkTitlesCli extends Maintenance {
	/// The constructor adds a description and one option.
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
	}

	/// Main function of the maintenance script.
	/// Will iterate over all pages in the wiki (starting at a certain index, 
	/// if the `--start` option is given) and call LinkTitles::processPage() for 
	/// each page.
	public function execute() {
		$index = intval($this->getOption('start', 0));
		if ( $index < 0 ) {
			$this->error('FATAL: Start index must be 0 or greater.', 1);
		};

		// Connect to the database
		$dbr = $this->getDB( DB_SLAVE );

		// Retrieve page names from the database.
		$res = $dbr->select( 
			'page',
			'page_title', 
			array( 
				'page_namespace = 0', 
			), 
			__METHOD__,
			array(
				'LIMIT' => 999999999,
				'OFFSET' => $index
			)
		);
		$numPages = $res->numRows();
		$context = RequestContext::getMain();
		$this->output("Processing ${numPages} pages, starting at index ${index}...\n");

		// Iterate through the pages; break if a time limit is exceeded.
		foreach ( $res as $row ) {
			$index += 1;
			$curTitle = $row->page_title;
			$this->output( 
				sprintf("\rPage #%d (%02.0f%%)", $index, $index / $numPages * 100)
		 	);
			LinkTitles::processPage($curTitle, $context);
		}

		$this->output("Finished parsing.");
	}
}
 
$maintClass = 'LinkTitlesCli';
if( defined('RUN_MAINTENANCE_IF_MAIN') ) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}

// vim: ts=2:sw=2:noet:comments^=\:///
