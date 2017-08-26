<?php
/**
 * @group bovender
 */
class DelimitersTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideStartOnly
	 */
	public function testDelimitersWordStartOnly( $enabled, $delimiter ) {
		$config = new LinkTitles\Config();
		$config->wordStartOnly = $enabled;
		LinkTitles\Delimiters::invalidate();
		$d = LinkTitles\Delimiters::default( $config );
		$this->assertSame( $delimiter, $d->wordStart );
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
	public function testDelimitersWordEndOnly( $enabled, $delimiter ) {
		$config = new LinkTitles\Config();
		$config->wordEndOnly = $enabled;
		LinkTitles\Delimiters::invalidate();
		$d = LinkTitles\Delimiters::default( $config );
		$this->assertSame( $delimiter, $d->wordEnd );
	}

	public static function provideEndOnly() {
		return [
			[ true, '(?!\pL)' ],
			[ false, '' ]
		];
	}
}
