<?php
/**
 * Tests the LinkTitles\Config class.
 *
 * This single unit test basically serves to ensure the Config class is working.
 * @group bovender
 * @group Database
 */
class ConfigTest extends LinkTitles\TestCase {

  public function testParseOnEdit() {
    $this->setMwGlobals( [
      'wgLinkTitlesParseOnEdit' => true,
      'wgLinkTitlesParseOnRender' => false
    ] );
    $config = new LinkTitles\Config();
    global $wgLinkTitlesParseOnEdit;
    $this->assertSame( $config->parseOnEdit, $wgLinkTitlesParseOnEdit );
  }
}
