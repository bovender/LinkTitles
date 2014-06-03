<?php
class SpecialLinkTitles extends SpecialPage {

	function __construct() {
		parent::__construct('LinkTitles');
	}

	function execute($par) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		// Determine whether this page was requested via GET or POST.
		// If GET, display information and a button to start linking.
		// If POST, start or continue the linking process.
		if ( $request->wasPosted() ) {
			if ( array_key_exists('s', $request->getValues()) ) {
				$this->process($request, $output);
			}
			else
			{
				$this->buildInfoPage($request, $output);
			}
		}
		else
		{
			$this->buildInfoPage($request, $output);
		}

	}


	/// Processes wiki articles, starting at the page indicated by 
	/// $startTitle. If $wgLinkTitlesTimeLimit is reached before all pages are 
	/// processed, returns the title of the next page that needs processing.
	private function process(&$request, &$output) {
		global $wgLinkTitlesTimeLimit;

		// Start the stopwatch
		$startTime = microtime(true);

		// Connect to the database
		$dbr = wfGetDB( DB_SLAVE );

		// Fetch the start index and max number of records from the POST 
		// request.
		$postValues = $request->getValues();

		// Convert the start index to an integer; this helps preventing
		// SQL injection attacks via forged POST requests.
		$start = intval($postValues['s']);

		// If an end index was given, we don't need to query the database
		if ( array_key_exists('e', $postValues) ) {
			$end = intval($postValues['e']);
		}
		else 
		{
			// No end index was given. Therefore, count pages now.
			$end = $this->countPages($dbr);
		};

		array_key_exists('r', $postValues) ?
				$reloads = $postValues['r'] :
				$reloads = 0;

		// Retrieve page names from the database.
		$res = $dbr->select( 
			'page',
			'page_title', 
			array( 
				'page_namespace = 0', 
			), 
			__METHOD__, 
			array(
				'OFFSET' => $start,
		 		'LIMIT' => '1'
			)
		);

		// Iterate through the pages; break if a time limit is exceeded.
		foreach ( $res as $row ) {
			$curTitle = $row->page_title;
			$this->processPage($curTitle);
			$start += 1;
			
			// Check if the time limit is exceeded
			if ( microtime(true)-$startTime > $wgLinkTitlesTimeLimit )
			{
				$reloads += 1;
				break;
			}
		}

		$this->addProgressInfo($output, $curTitle, $start, $end);

		// If we have not reached the last page yet, produce code to reload
		// the extension's special page.
		if ( $start <= $end )
	 	{
			// Build a form with hidden values and output JavaScript code that 
			// immediately submits the form in order to continue the process.
			$output->addHTML($this->getReloaderForm($request->getRequestURL(), 
				$start, $end, $reloads));
		}
	}

	/// Processes a single page, given a $title Title object.
	private function processPage($title) {
		// TODO: make this namespace-aware
		$titleObj = Title::makeTitle(0, $title);
		$page = WikiPage::factory($titleObj);
		$article = Article::newFromWikiPage($page, $this->getContext());
		$text = $article->getContent();
		LinkTitles::parseContent($article, $text);
		$content = new WikitextContent($text);
		$page->doEditContent($content,
			"Parsed for page titles by LinkTitles bot.",
			EDIT_MINOR | EDIT_FORCE_BOT
		);

	}

	/// Adds WikiText to the output containing information about the extension 
	/// and a form and button to start linking.
	private function buildInfoPage(&$request, &$output) {
		$url = $request->getRequestURL();

		// TODO: Put the page contents in messages in the i18n file.
		$output->addWikiText(
<<<EOF
LinkTitles extension: http://www.mediawiki.org/wiki/Extension:LinkTitles

Source code: http://github.com/bovender/LinkTitles

== Batch Linking ==
You can start a batch linking process by clicking on the button below.
This will go through every page in the normal namespace of your Wiki and 
insert links automatically. This page will repeatedly reload itself, in 
order to prevent blocking the server. To interrupt the process, simply
close this page.
EOF
		);
		$output->addHTML(
<<<EOF
<form method="post" action="${url}">
	<input type="submit" value="Start linking" />
	<input type="hidden" name="s" value="0" />
</form>
EOF
		);
	}

	/// Produces informative output in WikiText format to show while working.
	private function addProgressInfo($output, $curTitle, $start, $end) {
		$progress = $start / $end * 100;
		$percent = sprintf("%01.1f", $progress);

		$output->addWikiText(
<<<EOF
== Processing pages... ==
The [http://www.mediawiki.org/wiki/Extension:LinkTitles LinkTitles] 
extension is currently going through very page of your wiki, adding links to 
existing pages as appropriate.

=== Current page: $curTitle ===
EOF
		);
		$output->addHTML(
<<<EOF
<p>Page ${start} of ${end}.</p>
<div style="width:100%; padding:2px; border:1px solid #000; position: relative;
		margin-bottom:16px;">
	<span style="position: absolute; left: 50%; font-weight:bold; color:#555;">
		${percent}%
	</span>
	<div style="width:${progress}%; background-color:#bbb; height:20px; margin:0;"></div>
</div>
EOF
		);
		$output->addWikiText(
<<<EOF
=== To abort, close this page, or hit the 'Stop' button in your browser ===
[[Special:LinkTitles|Return to Special:LinkTitles.]]
EOF
		);
	}

	/// Generates an HTML form and JavaScript to automatically submit the 
	/// form.
	private function getReloaderForm($url, $start, $end, $reloads) {
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

	/// Counts the number of pages in a read-access wiki database ($dbr).
	private function countPages($dbr) {
		$res = $dbr->select(
			'page',
			'page_id', 
			array( 
				'page_namespace = 0', 
			), 
			__METHOD__ 
		);
		return $res->numRows();
	}
}
// vim: ts=2:sw=2:noet
