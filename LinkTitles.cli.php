<?php
/*
 *      \file LinkTitles.cli.php
 *      
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

// Include the maintenance base class from:
//   $wgScriptPath/maintenance/Maintenance.php
// Our script is normally located at:
//   $wgScriptPath/extensions/LinkTitles/LinkTitles.cli.php
require_once( "/home/daniel/Documents/Kommunikation/Wiki/maintenance/Maintenance.php" );
require_once( dirname( __FILE__ ) . "/LinkTitles.body.php" );
 
class LinkTitlesCli extends Maintenance {
	public function execute() {
		// Connect to the database
		$dbr = $this->getDB( DB_SLAVE );

		// Retrieve page names from the database.
		$res = $dbr->select( 
			'page',
			'page_title', 
			array( 
				'page_namespace = 0', 
			), 
			__METHOD__
		);
		$index = 0;
		$numPages = $res->numRows();
		$context = RequestContext::getMain();
		$this->output("Processing ${numPages} pages...\n");

		// Iterate through the pages; break if a time limit is exceeded.
		foreach ( $res as $row ) {
			$index += 1;
			$curTitle = $row->page_title;
			$this->output( sprintf("\r%02.0f%%", $index / $numPages * 100) );
			LinkTitles::processPage($curTitle, $context);
		}
	}
}
 
$maintClass = 'LinkTitlesCli';
if( defined('RUN_MAINTENANCE_IF_MAIN') ) {
	require_once( RUN_MAINTENANCE_IF_MAIN );
} else {
	require_once( DO_MAINTENANCE ); # Make this work on versions before 1.17
}

// vim: ts=2:sw=2:noet

