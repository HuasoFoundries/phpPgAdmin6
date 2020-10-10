VERSION = $(shell cat composer.json | sed -n 's/.*"version": "\([^"]*\)"/\1/p')

SHELL = /usr/bin/env bash

HAS_PSALM := $(shell ls ./vendor/bin/xpsalm 2> /dev/null)
XDSWI := $(shell command -v xd_swi 2> /dev/null)
HAS_PHPMD := $(shell command -v phpmd 2> /dev/null)
HAS_CSFIXER:= $(shell command -v php-cs-fixer 2> /dev/null)
XDSWI_STATUS:=$(shell command xd_swi stat 2> /dev/null)
HAS_PHIVE:=$(shell command phive --version 2> /dev/null)
CURRENT_BRANCH:=$(shell command git rev-parse --abbrev-ref HEAD 2> /dev/null)
DATENOW:=`date +'%Y-%m-%d'`
YELLOW=\033[0;33m
RED=\033[0;31m
WHITE=\033[0m
GREEN=\u001B[32m


csfixer:
	@if [ -f "vendor/bin/php-cs-fixer" ]; then \
		echo "XDEBUG was: "$(XDSWI_STATUS) ;\
		${MAKE} disable_xdebug  --no-print-directory ;\
		mkdir -p .build/php-cs-fixer ;\
        vendor/bin/php-cs-fixer fix --config=.php_cs.php --diff --diff-format=udiff --dry-run --verbose ;\
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
	    phpmd src test .phpmd.xml |  sed "s/.*\///" ;\
    fi ;\
    echo ""





psalm:
	@${MAKE} disable_xdebug  --no-print-directory 
	@if [ ! -f "vendor/bin/psalm" ]; then \
		echo -e "$(GREEN)psalm$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
		echo -e "Install it with $(GREEN)composer require --dev vimeo/psalm$(WHITE)" ;\
		exit 0 ;\
	fi
	
	@mkdir -p .build/psalm ;\
	vendor/bin/psalm --show-info=false --long-progress --threads=2 --config=psalm.xml | tee temp/psalm.output.txt
	@${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory ;\
	echo ""

phpstan:
	@${MAKE} disable_xdebug  --no-print-directory 
	@if [ ! -f "vendor/bin/phpstan" ]; then \
		echo -e "$(GREEN)phpstan$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
		echo -e "Install it with $(GREEN)composer require --dev phpstan/phpstan$(WHITE)" ;\
		exit 0 ;\
	fi
	
	@mkdir -p .build/phpstan ;\
	./vendor/bin/phpstan analyse --memory-limit=2G   --configuration phpstan.neon  | tee temp/phpstan.output.txt
	@${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory ;\
	echo ""

lint:
	@if [ -f "vendor/bin/parallel-lint" ]; then \
		mkdir -p .build/parallel ;\
		${MAKE} disable_xdebug  --no-print-directory ;\
		vendor/bin/parallel-lint --ignore-fails --exclude vendor  src ;\
		${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory;\
	else \
		echo -e "$(GREEN)parallel-lint$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
		echo -e "Install it with $(GREEN)composer require --dev php-parallel-lint/php-parallel-lint$(WHITE)" ;\
	fi
	@find ./src -name \*.php -print0 | xargs -0 -n 1 php -l
	@echo ""

update_baselines:
	@${MAKE} disable_xdebug  --no-print-directory ;\
    find .build/phpstan -mtime +5 -type f -name "*.php" -exec rm -rf {} \;
	@vendor/bin/phpstan analyze --configuration phpstan.neon --generate-baseline ;\
	find .build/psalm -mtime +5 -type f   -exec rm -rf {} \;
	@vendor/bin/psalm --config=psalm.xml --update-baseline --ignore-baseline  --set-baseline=psalm-baseline.xml ;\
	${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory


fixers: lint csfixer dependency-analysis phpmd psalm phpstan



.PHONY: dependency-analysis
dependency-analysis: vendor ## Runs a dependency analysis with maglnet/composer-require-checker
	tools/composer-require-checker check --config-file=$(shell pwd)/composer-require-checker.json


install_dev_deps:
	@if [ "$(HAS_PHIVE)" == "" ]; then \
        echo -e "$(GREEN)phive$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
        echo -e "Visit $(GREEN)https://github.com/phar-io/phive$(WHITE) and follow install procedure" ;\
    else \
	    phive install phpmd ;\
		phive install phpcpd ;\
		phive install phpcs ;\
		phive install composer-require-checker ;\
		curl -sfL https://raw.githubusercontent.com/reviewdog/reviewdog/master/install.sh | sh -s -- -b tools ;\
    fi ;\
    echo ""

	
reviewdog:
	@tools/reviewdog -diff="git diff develop"