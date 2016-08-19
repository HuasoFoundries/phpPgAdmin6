VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

SHELL = /usr/bin/env bash

default: clean fix-permissions install
.PHONY: default fix-permissions install

version:
	@echo $(VERSION)



clean:
	git clean -fd
	git checkout .
	git pull
	

fix-permissions:
	sudo chmod 777 temp -R
	sudo chown www-data:www-data temp -R
	

install: 
	composer install --no-dev

update:
	git clean -fd
	git checkout .
	git pull
	composer install

update_version:
	@echo "Current version is " ${VERSION}
	@echo "Next version is " $(v)
	sed -i s/"$(VERSION)"/"$(v)"/g composer.json
	composer update nothing

tag_and_push:
		git add --all
		git commit -a -m "Tag v $(v) $(m)"
		git tag v$(v)
		git push
		git push --tags

tag: update_version build tag_and_push	