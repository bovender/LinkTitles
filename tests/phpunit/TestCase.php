<?php
namespace LinkTitles;

abstract class TestCase extends \MediaWikiTestCase {
  protected function setUp() {
    parent::setUp();
    $this->insertPage( 'link target', 'This page serves as a link target' );
    Targets::invalidate(); // force re-querying the pages table
  }

  protected function tearDown() {
    parent::tearDown();
  }

  protected function getPageText( \WikiPage $page ) {
    $content = $page->getContent();
    return $page->getContentHandler()->serializeContent( $content );
  }
}
