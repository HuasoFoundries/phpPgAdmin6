VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

SHELL = /usr/bin/env bash

default: install
.PHONY: tag install test csfixer

version:
	@echo $(VERSION)



install: 
	sudo rm -R --force temp/twigcache/*
	composer install --no-dev
	chmod 777 temp -R


update_version:
	@echo "Current version is " ${VERSION}
	@echo "Next version is " $(v)
	@sed -i s/"$(VERSION)"/"$(v)"/g composer.json
	composer update nothing --lock --root-reqs --prefer-dist

tag_and_push:
		git add --all
		git commit -a -m "Tag v $(v) $(m)"
		git tag v$(v)
		git push
		git push --tags
		git checkout master
		git merge develop
		git push
		git checkout develop

tag: test update_version csfixer tag_and_push	

test:
ifeq ("$(wildcard config.inc.php)","")
	cp config.inc.php-dist config.inc.php
endif	
	./vendor/bin/codecept run unit --debug

csfixer:
	./vendor/bin/php-cs-fixer --verbose fix	