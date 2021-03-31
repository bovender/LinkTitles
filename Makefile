.PHONY: test

test:
	# With MW version 1.34, there is one test that is expected to fail
	# because linking on page save no longer works with MediaWiki 1.32
	# and newer. The test that is expected to fail is:
	# ExtensionTest::testParseOnEdit with data set #0
	docker run -it -v `pwd`:/var/www/html/extensions/LinkTitles --rm bovender/linktitles

build-test-container:
	docker build -t bovender/linktitles .
