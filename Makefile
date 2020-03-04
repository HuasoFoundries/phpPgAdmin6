VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)"/\1/p')

SHELL = /usr/bin/env bash

HAS_PSALM := $(shell ls ./vendor/bin/xpsalm 2> /dev/null)
XDSWI := $(shell command -v xd_swi 2> /dev/null)
HAS_PHPMD := $(shell command -v phpmd 2> /dev/null)
HAS_CSFIXER:= $(shell command -v php-cs-fixer 2> /dev/null)
XDSWI_STATUS:=$(shell command xd_swi stat 2> /dev/null)
CURRENT_BRANCH:=$(shell command git rev-parse --abbrev-ref HEAD 2> /dev/null)
DATENOW:=`date +'%Y-%m-%d'`
YELLOW=\033[0;33m
RED=\033[0;31m
WHITE=\033[0m
GREEN=\u001B[32m


default: install
.PHONY: tag install test csfixer create_testdb destroy_testdb run_local phpmd

version:
	@echo -e "Current version is: ${GREEN} $(VERSION) ${WHITE}" ;\
	echo -e "  master branch closest tag is $(GREEN)" `	git describe --tags master `;\
	echo -e "$(WHITE)  develop branch closest tag is $(GREEN)"  `	git describe --tags develop `;\
	echo -e "$(WHITE) "


install: fix_permissions composer_update
install: 
	@composer install --no-interaction --no-progress --no-suggest --prefer-dist ;\
	composer validate --strict ;\
	composer normalize



fix_permissions:
	@sudo chmod 777 temp -R ;\
	sudo chown -R $$USER:www-data temp/sessions ;\
	sudo chown -R $$USER:www-data temp/twigcache ;\
	sudo rm -R --force temp/twigcache/*

composer_update:
	@echo -e "updating composer...${YELLOW}--lock --root-reqs --prefer-dist --prefer-stable --no-suggest -a${WHITE}" ;\
	composer update  --lock --root-reqs --prefer-dist --prefer-stable --no-suggest -a

update_version:
	@echo "Current version is " ${VERSION} ;\
	echo "Next version is " $(v) ;\
	sed -i 's/"version": "$(VERSION)"/"version": "$(v)"/g' composer.json
	@${MAKE} composer_update --no-print-directory


mocktag:
	echo "Creating tag Tag v$(v) at $(DATENOW) - $(m)"


tag_and_push:
	@git commit -a -m "Creating Tag v$(v) at $(DATENOW) - $(m)" ;\
	git push ;\
	if [[ "$(CURRENT_BRANCH)" != "master" ]]; then \
		git checkout master ;\
		git merge $(CURRENT_BRANCH) ;\
	fi 
	
	git tag v$(v) ;\
	git push ;\
	git push --tags ;\
	git checkout $(CURRENT_BRANCH)



tag: test update_version csfixer tag_and_push	

test:
ifeq ("$(wildcard config.inc.php)","")
	cp config.inc.php-dist config.inc.php
endif
	./vendor/bin/codecept run unit --debug
	find ./src -name \*.php -print0 | xargs -0 -n 1 php -l


csfixer:
	@if [ -f "vendor/bin/php-cs-fixer" ]; then \
		echo "XDEBUG was: "$(XDSWI_STATUS) ;\
		${MAKE} disable_xdebug  --no-print-directory ;\
		mkdir -p .build/php-cs-fixer ;\
        vendor/bin/php-cs-fixer fix --config=.php_cs --verbose ;\
		${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory;\
    else \
        echo -e "$(GREEN)php-cs-fixer$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
        echo -e "Install it with $(GREEN)composer install --dev friendsofphp/php-cs-fixer$(WHITE)" ;\
    fi ;\
	sudo rm -rf temp/route.cache.php



disable_xdebug:
	@if [[ "$(XDSWI)" != "" ]]; then \
    	xd_swi off ;\
    fi 

enable_xdebug:
	@if [[ "$(XDSWI)" != "" ]]; then \
    	xd_swi $(new_status) ;\
    fi 

phpmd:
	@if [ "$(HAS_PHPMD)" == "" ]; then \
        echo -e "$(GREEN)phpmd$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
        echo -e "Install it with $(GREEN)phive install phpmd$(WHITE)" ;\
    else \
	    phpmd src text .phpmd.xml |  sed "s/.*\///" ;\
    fi ;\
    echo ""

var_dumper:
	@if [ -f "vendor/bin/var-dump-server" ]; then \
		vendor/bin/var-dump-server ;\
	else \
		 echo -e "$(GREEN)symfony/var-dumper$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
        echo -e "Install it with $(GREEN)composer require --dev symfony/var-dumper$(WHITE)" ;\
	fi;
	@echo ""

folder ?= src
psalm: FOLDER_BASENAME:=`basename $(folder)|sed 's/src//'`
psalm:
	@if [ -f "vendor/bin/psalm" ]; then \
		mkdir -p .build/psalm ;\
		${MAKE} disable_xdebug  --no-print-directory ;\
		vendor/bin/psalm --show-info=false \
			  --config=psalm.xml \
			  --set-baseline=.build/psalm/psalm-baseline$(FOLDER_BASENAME).xml \
			  --shepherd $(folder) ;\
		${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory;\
	else \
	 	echo -e "$(GREEN)vimeo/psalm$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
		echo -e "Install it with $(GREEN)composer require --dev vimeo/psalm$(WHITE)" ;\
	fi
	@echo ""


phpstan:
	@${MAKE} disable_xdebug  --no-print-directory 
	@if [ ! -f "vendor/bin/phpstan" ]; then \
		echo -e "$(GREEN)phpstan$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
		echo -e "Install it with $(GREEN)composer require --dev phpstan/phpstan$(WHITE)" ;\
		exit 0 ;\
	fi
	
	@mkdir -p .build/phpstan ;\
	./vendor/bin/phpstan analyse --memory-limit=2G   ${error_format}  
	@${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory ;\
	echo ""


fixers: phpmd psalm phpstan

create_testdb:
	PGPASSWORD=scrutinizer psql   -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_install.sql

destroy_testdb:
	PGPASSWORD=scrutinizer psql   -U scrutinizer -h localhost -f tests/simpletest/data/ppatests_remove.sql	

run_local:
	${MAKE} fix_permissions
	php -S localhost:8000 index.php	