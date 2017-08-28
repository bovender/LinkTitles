<?php
/**
 * @group bovender
 * @group Database
 */
class LinkTitlesLinkerTest extends LinkTitles\TestCase {
  protected $title;

  protected function setUp() {
    $this->title = $this->insertPage( 'source page', 'This page is the test page' )['title'];
    parent::setUp(); // call last to have the Targets object invalidated after inserting the page
  }

  /**
   * @dataProvider provideLinkContentSmartModeData
   */
  public function testLinkContentSmartMode( $capitalLinks, $smartMode, $input, $expectedOutput) {
    $this->setMwGlobals( 'wgCapitalLinks', $capitalLinks );
    $config = new LinkTitles\Config();
    $config->smartMode = $smartMode;
    $linker = new LinkTitles\Linker( $config );
    $this->assertSame( $expectedOutput, $linker->linkContent( $this->title, $input ));
  }

  public static function provideLinkContentSmartModeData() {
    return [
      [
        true, // wgCapitalLinks
        true, // smartMode
        'With smart mode on and $wgCapitalLinks = true, this page should link to link target',
        'With smart mode on and $wgCapitalLinks = true, this page should link to [[link target]]'
      ],
      [
        true, // wgCapitalLinks
        false, // smartMode
        'With smart mode off and $wgCapitalLinks = true, this page should link to link target',
        'With smart mode off and $wgCapitalLinks = true, this page should link to [[link target]]'
      ],
      [
        true, // wgCapitalLinks
        true, // smartMode
        'With smart mode on and $wgCapitalLinks = true, this page should link to Link Target',
        'With smart mode on and $wgCapitalLinks = true, this page should link to [[Link target|Link Target]]'
      ],
      [
        true, // wgCapitalLinks
        false, // smartMode
        'With smart mode off and $wgCapitalLinks = true, this page should not link to Link Target',
        'With smart mode off and $wgCapitalLinks = true, this page should not link to Link Target'
      ],
      [
        false, // wgCapitalLinks
        true, // smartMode
        'With smart mode on and $wgCapitalLinks = false, this page should link to Link target',
        'With smart mode on and $wgCapitalLinks = false, this page should link to [[Link target]]'
      ],
      [
        false, // wgCapitalLinks
        true, // smartMode
        'With smart mode on and $wgCapitalLinks = false, this page should link to link target',
        'With smart mode on and $wgCapitalLinks = false, this page should link to [[Link target|link target]]'
      ],
      [
        false, // wgCapitalLinks
        false, // smartMode
        'With smart mode off and $wgCapitalLinks = false, this page should not link to link target',
        'With smart mode off and $wgCapitalLinks = false, this page should not link to link target'
      ],
      [
        false, // wgCapitalLinks
        false, // smartMode
        'With smart mode off and $wgCapitalLinks = false, this page should not link to Link target',
        'With smart mode off and $wgCapitalLinks = false, this page should not link to [[Link target]]'
      ],
      [
        false, // wgCapitalLinks
        true, // smartMode
        'With smart mode on and $wgCapitalLinks = false, this page should link to Link Target',
        'With smart mode on and $wgCapitalLinks = false, this page should link to [[Link target|Link Target]]'
      ],
      [
        false, // wgCapitalLinks
        false, // smartMode
        'With smart mode off and $wgCapitalLinks = false, this page should not link to Link Target',
        'With smart mode off and $wgCapitalLinks = false, this page should not link to Link Target'
      ],
    ];
  }
}
