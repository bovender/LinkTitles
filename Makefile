.PHONY: test

test:
	docker run -it --rm bovender/linktitles

build-test-container:
	docker build -t bovender/linktitles .
