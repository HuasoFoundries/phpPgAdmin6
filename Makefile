VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)",/\1/p')

SHELL = /usr/bin/env bash

XDSWI := $(shell command -v xd_swi 2> /dev/null)
HAS_PHPMD := $(shell command -v phpmd 2> /dev/null)
HAS_CSFIXER:= $(shell command -v php-cs-fixer 2> /dev/null)
XDSWI_STATUS:=$(shell command xd_swi stat 2> /dev/null)
DATENOW:=`date +'%Y-%m-%d'`
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
fix_permissions:
	sudo chmod 777 temp -R	

update_version:
	@echo "Current version is " ${VERSION} ;\
	echo "Next version is " $(v) ;\
	sed -i s/"$(VERSION)"/"$(v)"/g composer.json
	composer update nothing --lock --root-reqs --prefer-dist


mocktag:
	echo "Creating tag Tag v$(v) at $(DATENOW) - $(m)"


tag_and_push:

		git checkout master
		git merge develop
		git rm --cached tests/selenium -r
		git rm --cached tests/simpletest -r
		git commit -a -m "Creating tag Tag v$(v) at $(DATENOW) - $(m)"
		#git tag v$(v)
		#git push
		#git push --tags
		git checkout develop
		git reset --soft master
		git commit -am "Return to develop after tag v$(v)"



tag: test update_version csfixer tag_and_push	

test:
ifeq ("$(wildcard config.inc.php)","")
	cp config.inc.php-dist config.inc.php
endif
	./vendor/bin/codecept run unit --debug

runcsfixer:
		@if [[ "$(HAS_CSFIXER)" == "" ]]; then \
        echo -e "$(GREEN)php-cs-fixer$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
        echo -e "Install it with $(GREEN)phive install php-cs-fixer global$(WHITE)" ;\
    else \
	    php-cs-fixer --verbose fix ;\
	    php-cs-fixer --verbose fix index.php ;\
    fi 

csfixer:
	@if [[ "$(XDSWI)" == "" ]]; then \
	     ${MAKE} runcsfixer --no-print-directory ;\
    else \
        xd_swi off ;\
		${MAKE} runcsfixer --no-print-directory ;\
		xd_swi $(XDSWI_STATUS)	;\
    fi
	
phpmd:
	@if [ "$(HAS_PHPMD)" == "" ]; then \
        echo -e "$(GREEN)phpmd$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
        echo -e "Install it with $(GREEN)phive install phpmd$(WHITE)" ;\
    else \
	    phpmd src text .phpmd.xml |  sed "s/.*\///" ;\
    fi ;\
    echo ""
	


create_testdb:
	PGPASSWORD=scrutinizer psql   -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_install.sql

destroy_testdb:
	PGPASSWORD=scrutinizer psql   -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_remove.sql	

run_local:
	${MAKE} fix_permissions
	php -S localhost:8000 index.php	