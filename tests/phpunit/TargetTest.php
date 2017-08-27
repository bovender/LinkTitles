<?php
/**
 * @group bovender
 */
class TargetTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideStartOnly
	 */
	public function testTargetWordStartOnly( $enabled, $delimiter ) {
		$config = new LinkTitles\Config();
		$config->wordStartOnly = $enabled;
		$target = new LinKTitles\Target( NS_MAIN, 'test page', $config );
		$this->assertSame( $delimiter, $target->wordStart );
	}

	public static function provideStartOnly() {
		return [
			[ true, '(?<!\pL)' ],
			[ false, '' ]
		];
	}

	/**
	 * @dataProvider provideEndOnly
	 */
	public function testTargetWordEndOnly( $enabled, $delimiter ) {
		$config = new LinkTitles\Config();
		$config->wordEndOnly = $enabled;
		$target = new LinKTitles\Target( NS_MAIN, 'test page', $config );
		$this->assertSame( $delimiter, $target->wordEnd );
	}

	public static function provideEndOnly() {
		return [
			[ true, '(?!\pL)' ],
			[ false, '' ]
		];
	}
}
