VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

SHELL = /usr/bin/env bash

XDSWI := $(shell command -v xd_swi 2> /dev/null)
HASPHPMD := $(shell command -v phpmd 2> /dev/null)

YELLOW=\033[0;33m
RED=\033[0;31m
WHITE=\033[0m
GREEN=\u001B[32m


default: install
.PHONY: tag install test csfixer create_testdb destroy_testdb run_local phpmd

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

runcsfixer:
	@if [ -f ./vendor/bin/php-cs-fixer ]; then \
	    ./vendor/bin/php-cs-fixer --verbose fix ;\
	    ./vendor/bin/php-cs-fixer --verbose fix index.php ;\
    else \
        echo -e "$(GREEN)php-cs-fixer$(WHITE) is $(RED)NOT$(WHITE) installed. Install it with $(GREEN)composer require --dev friendsofphp/php-cs-fixer$(WHITE)" ;\
    fi 

csfixer:
	@if [[ "$(XDSWI)" == "" ]]; then \
	     ${MAKE} runcsfixer --no-print-directory ;\
    else \
        xd_swi off ;\
		${MAKE} runcsfixer --no-print-directory ;\
		xd_swi on	;\
    fi
	
phpmd:
	@if [ -f ./vendor/bin/phpmd ]; then \
	    ./vendor/bin/phpmd $(target) text .phpmd.xml |  sed "s/.*\///" ;\
    else \
        echo -e "$(GREEN)phpmd$(WHITE) is $(RED)NOT$(WHITE) installed. Install it with $(GREEN)composer require --dev phpmd/phpmd$(WHITE)" ;\
    fi ;\
    echo ""
	


create_testdb:
	PGPASSWORD=scrutinizer psql -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_install.sql

destroy_testdb:
	PGPASSWORD=scrutinizer psql -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_remove.sql	

run_local:
	${MAKE} fix_permissions
	php -S localhost:8000 index.php	