VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

SHELL = /usr/bin/env bash

default: install
.PHONY: tag install test

version:
	@echo $(VERSION)



install: 
	sudo rm -R --force temp/twigcache/*
	composer install --no-dev
	chmod 777 temp -R


update_version:
	@echo "Current version is " ${VERSION}
	@echo "Next version is " $(v)
	sed -i s/"$(VERSION)"/"$(v)"/g composer.json
	composer update nothing --lock --root-reqs

tag_and_push:
		git add --all
		git commit -a -m "Tag v $(v) $(m)"
		git tag v$(v)
		git push
		git push --tags

tag: test update_version tag_and_push	

test:
	./vendor/bin/codecept run unit --debug