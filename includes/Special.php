<?php
/**
 * Provides a special page for the LinkTitles extension.
 *
 * Copyright 2012-2018 Daniel Kraus <bovender@bovender.de> ('bovender')
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
/// @defgroup batch Batch processing

/// @cond
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}
/// @endcond

/**
 * Provides a special page that can be used to batch-process all pages in
 * the wiki. By default, this can only be performed by sysops.
 * @ingroup batch
 *
 */
class Special extends \SpecialPage {
	private $config;

	/**
	 * Constructor. Announces the special page title and required user right to the parent constructor.
	 */
	function __construct() {
		// the second parameter in the following function call ensures that only
		// users who have the 'linktitles-batch' right get to see this page (by
		// default, this are all sysop users).
		parent::__construct( 'LinkTitles', 'linktitles-batch' );
		$this->config = new Config();
	}

	function getGroupName() {
		return 'pagetools';
	}


	/**
	 * Entry function of the special page class. Will abort if the user does not have appropriate permissions ('linktitles-batch').
	 * @param  $par Additional parameters (required by interface; currently not used)
	 */
	function execute( $par ) {
		// Prevent non-authorized users from executing the batch processing.
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		// Determine whether this page was requested via GET or POST.
		// If GET, display information and a button to start linking.
		// If POST, start or continue the linking process.
		if ( $request->wasPosted() ) {
			if ( array_key_exists( 's', $request->getValues() ) ) {
				$this->process( $request, $output );
			}
			else
			{
				$this->buildInfoPage( $request, $output );
			}
		}
		else
		{
			$this->buildInfoPage( $request, $output );
		}
	}

	/**
	 * Processes wiki articles, starting at the page indicated by
	 * $startTitle. If $wgLinkTitlesTimeLimit is reached before all pages are
	 * processed, returns the title of the next page that needs processing.
	 * @param WebRequest $request WebRequest object that is associated with the special page.
	 * @param OutputPage $output  Output page for the special page.
	 */
	private function process( \WebRequest &$request, \OutputPage &$output) {
		// get our Namespaces
		$namespacesClause = str_replace( '_', ' ','(' . implode( ', ',$this->config->sourceNamespaces ) . ')' );

		// Start the stopwatch
		$startTime = microtime( true );

		// Connect to the database
		$dbr = wfGetDB( DB_SLAVE );

		// Fetch the start index and max number of records from the POST
		// request.
		$postValues = $request->getValues();

		// Convert the start index to an integer; this helps preventing
		// SQL injection attacks via forged POST requests.
		$start = intval( $postValues['s'] );

		// If an end index was given, we don't need to query the database
		if ( array_key_exists( 'e', $postValues ) ) {
			$end = intval( $postValues['e'] );
		}
		else
		{
			// No end index was given. Therefore, count pages now.
			$end = $this->countPages( $dbr, $namespacesClause );
		};

		array_key_exists( 'r', $postValues ) ? $reloads = $postValues['r'] : $reloads = 0;

		// Retrieve page names from the database.
		$res = $dbr->select(
			'page',
			array('page_title', 'page_namespace'),
			array(
				'page_namespace IN ' . $namespacesClause,
			),
			__METHOD__,
			array(
				'LIMIT' => 999999999,
				'OFFSET' => $start
			)
		);

		// Iterate through the pages; break if a time limit is exceeded.
		foreach ( $res as $row ) {
			$curTitle = \Title::makeTitleSafe( $row->page_namespace, $row->page_title);
			Extension::processPage( $curTitle, $this->getContext() );
			$start += 1;

			// Check if the time limit is exceeded
			if ( microtime( true ) - $startTime > $this->config->specialPageReloadAfter )
			{
				break;
			}
		}

		$this->addProgressInfo( $output, $curTitle, $start, $end );

		// If we have not reached the last page yet, produce code to reload
		// the extension's special page.
		if ( $start < $end )
		{
			$reloads += 1;
			// Build a form with hidden values and output JavaScript code that
			// immediately submits the form in order to continue the process.
			$output->addHTML( $this->getReloaderForm( $request->getRequestURL(),
				$start, $end, $reloads) );
		}
		else // Last page has been processed
		{
			$this->addCompletedInfo( $output, $start, $end, $reloads );
		}
	}

	/*
	 * Adds WikiText to the output containing information about the extension
	 * and a form and button to start linking.
	 */
	private function buildInfoPage( &$request, &$output ) {
		$output->addWikiMsg( 'linktitles-special-info', Extension::URL );
		$url = $request->getRequestURL();
		$submitButtonLabel = $this->msg( 'linktitles-special-submit' );
		$output->addHTML(
<<<EOF
<form method="post" action="${url}">
	<input type="submit" value="$submitButtonLabel" />
	<input type="hidden" name="s" value="0" />
</form>
EOF
		);
	}

  /*
	 * Produces informative output in WikiText format to show while working.
	 * @param $output    Output object.
	 * @param $curTitle  Title of the currently processed page.
	 * @param $index     Index of the currently processed page.
	 * @param $end       Last index that will be processed (i.e., number of pages).
	 */
	private function addProgressInfo( &$output, $curTitle, $index, $end ) {
		$progress = $index / $end * 100;
		$percent = sprintf("%01.1f", $progress);

		$output->addWikiMsg( 'linktitles-special-progress', Extension::URL, $curTitle );
		$pageInfo = $this->msg( 'linktitles-page-count', $index, $end );
		$output->addWikiMsg( 'linktitles-special-page-count', $index, $end );
		$output->addHTML( // TODO: do not use the style attribute (to make it work with CSP-enabled sites)
<<<EOF
<div style="width:100%; padding:2px; border:1px solid #000; position: relative; margin-bottom:16px;">
	<span style="position: absolute; left: 50%; font-weight:bold; color:#555;">${percent}%</span>
	<div style="width:${progress}%; background-color:#bbb; height:20px; margin:0;"></div>
</div>
EOF
		);
		$output->addWikiMsg( 'linktitles-special-cancel-notice' );
	}

	/**
	 * Generates an HTML form and JavaScript to automatically submit the
	 * form.
	 * @param $url     URL to reload with a POST request.
	 * @param $start   Index of the next page that shall be processed.
	 * @param $end     Index of the last page to be processed.
	 * @param $reloads Counter that holds the number of reloads so far.
	 * @return         String that holds the HTML for a form and a JavaScript command.
	 */
	private function getReloaderForm( $url, $start, $end, $reloads ) {
		return
<<<EOF
<form method="post" name="linktitles" action="${url}">
	<input type="hidden" name="s" value="${start}" />
	<input type="hidden" name="e" value="${end}" />
	<input type="hidden" name="r" value="${reloads}" />
</form>
<script type="text/javascript">
	document.linktitles.submit();
</script>
EOF
		;
	}

  /**
	 * Adds statistics to the page when all processing is done.
	 * @param $output  Output object
	 * @param $start   Index of the first page that was processed.
	 * @param $end     Index of the last processed page.
	 * @param $reloads Number of reloads of the page.
	 * @return undefined
	 */
	private function addCompletedInfo( &$output, $start, $end, $reloads ) {
		$pagesPerReload = sprintf('%0.1f', $end / $reloads);
		$output->addWikiMsg( 'linktitltes-special-completed-info', $end,
			$config->specialPageReloadAfter, $reloads, $pagesPerReload
		);
	}

	/**
	 * Counts the number of pages in a read-access wiki database ($dbr).
	 * @param $dbr Read-only `Database` object.
	 * @return Number of pages in the default namespace (0) of the wiki.
	 */
	private function countPages( &$dbr, $namespacesClause ) {
		$res = $dbr->select(
			'page',
			array('pagecount' => "COUNT(page_id)"),
			array(
				'page_namespace IN ' . $namespacesClause,
			),
			__METHOD__
		);

		return $res->current()->pagecount;
	}
}

// vim: ts=2:sw=2:noet:comments^=\:///
