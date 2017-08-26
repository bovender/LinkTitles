<?php
/**
 * @group bovender
 * @group Database
 */
class ParseOnEditTest extends LinkTitles\TestCase {

  public function testParseOnEdit() {
    $this->setMwGlobals( [
      'wgLinkTitlesParseOnEdit' => true,
      'wgLinkTitlesParseOnRender' => false
    ] );
    $pageId = $this->insertPage( 'test page', 'This page should link to the link target' )['id'];
    $page = WikiPage::newFromId( $pageId );
    $this->assertSame( 'This page should link to the [[link target]]', self::getPageText( $page ) );
  }

  public function testDoNotParseOnEdit() {
    $this->setMwGlobals( [
      'wgLinkTitlesParseOnEdit' => false,
      'wgLinkTitlesParseOnRender' => false
    ] );
    $pageId = $this->insertPage( 'test page', 'This page should not link to the link target' )['id'];
    $page = WikiPage::newFromId( $pageId );
    $this->assertSame( 'This page should not link to the link target', self::getPageText( $page ) );
  }
}
