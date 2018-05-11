VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

SHELL = /usr/bin/env bash

default: install
.PHONY: tag install test csfixer create_testdb destroy_testdb run_local

version:
	@echo $(VERSION)



install: 
	sudo rm -R --force temp/twigcache/*
	composer install --no-dev
	chmod 777 temp -R

fix_permissions:
	sudo chmod 777 temp -R	

update_version:
	@echo "Current version is " ${VERSION} ;\
	echo "Next version is " $(v) ;\
	sed -i s/"$(VERSION)"/"$(v)"/g composer.json
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
	xd_swi off ;\
	./vendor/bin/php-cs-fixer --verbose fix ;\
	xd_swi on	

create_testdb:
	PGPASSWORD=scrutinizer psql -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_install.sql

destroy_testdb:
	PGPASSWORD=scrutinizer psql -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_remove.sql	

run_local:
	${MAKE} fix_permissions
	php -S localhost:8000 index.php	