<?php
/**
 * @group bovender
 */
class DelimitersTest extends MediaWikiTestCase {
	/**
	 * @dataProvider provideSplitData
	 */
	public function testSplit( $input, $output ) {

	}

	public static function provideSplitData() {
		return [
			[
				'this may be linked [[this may not be linked]]',
				[ 'this may be linked', '[[this may not be linked]]']
			]
		];
	}
}
