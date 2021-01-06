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




disable_xdebug:
	@if [[ "$(XDSWI)" != "" ]]; then \
    	xd_swi off ;\
    fi 

enable_xdebug:
	@if [[ "$(XDSWI)" != "" ]]; then \
    	xd_swi $(new_status) ;\
    fi 

abort_suggesting_composer:
	@if [ "0" != "$(XDSWI_STATUS)" ]; then \
		 $(YELLOW)Warn: $(GREEN)xdebug$(WHITE) is enabled. Just saying... ;\
	fi
	@if [ ! -f "$(executable)" ]; then \
		echo -e "$(GREEN)$(package_name)$(WHITE) $(RED)NOT FOUND$(WHITE) on $(CYAN)$(executable)$(WHITE). " ;\
		echo -e "Install it with $(GREEN)composer require --dev $(package_name)$(WHITE)" ;\
		echo ;\
		exit 1 ;\
	fi

check_executable_or_exit_with_phive:
	@if [ ! -e "$(executable)" ]; then \
		echo -e "$(GREEN)$(package_name)$(WHITE) $(RED)NOT FOUND$(WHITE) on $(CYAN)$(executable)$(WHITE). " ;\
		echo -e "Install it with $(GREEN)phive install $(package_name)$(WHITE)" ;\
		echo ;\
		exit 1 ;\
	fi
	@if [ "0" != "$(XDSWI_STATUS)" ]; then \
		 $(YELLOW)Warn: $(GREEN)xdebug$(WHITE) is enabled. Just saying... ;\
	fi


update_baselines:
	@${MAKE} disable_xdebug  --no-print-directory ;\
	find .build/phpstan -mtime +5 -type f -name "*.php" -exec rm -rf {} \;
	@vendor/bin/phpstan analyze --configuration phpstan.neon --generate-baseline ;\
	find .build/psalm -mtime +5 -type f   -exec rm -rf {} \;
	@vendor/bin/psalm --config=psalm.xml --update-baseline --ignore-baseline  --set-baseline=psalm-baseline.xml ;\
	${MAKE} enable_xdebug new_status=$(XDSWI_STATUS)  --no-print-directory

.PHONY:abort_suggesting_composer check_executable_or_exit_with_phive update_baselines

phpmd:
	$(eval executable:=vendor/bin/phpmd)
	$(eval package_name:=phpmd/phpmd)
ifeq (,$(reportformat))
	$(eval reportformat='ansi')
endif
	@${MAKE} abort_suggesting_composer  executable=$(executable) package_name=$(package_name) --no-print-directory 
	@$(executable) src $(reportformat) .phpmd.xml --exclude=src/help/*,src/translations/*

phpmd_checkstyle:
	$(eval executable:=vendor/bin/phpmd)
	$(eval package_name:=phpmd/phpmd)
ifeq (,$(reportformat))
	$(eval reportformat='ansi')
endif
	@${MAKE} abort_suggesting_composer  executable=$(executable) package_name=$(package_name) --no-print-directory 
	@$(executable) src  PHPPgAdmin\\\CheckStyleRenderer .phpmd.xml --exclude=src/help/*,src/translations/* | vendor/bin/cs2pr --colorize ;\
	echo -e "$(GREEN)Formatted PHPMD$(WHITE): as checkStyle"
	

csfixer:
	$(eval executable:=vendor/bin/php-cs-fixer)
	$(eval package_name:=friendsofphp/php-cs-fixer)
ifeq (,$(reportformat))
	$(eval reportformat='txt')
endif
	@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory
	@mkdir -p .build/phpcs && touch .build/phpcs/csfixer.cache ;\
	vendor/bin/php-cs-fixer fix --config=.php_cs.php --cache-file=.build/phpcs/csfixer.cache --format=$(reportformat) $(dry_run)  --diff

csfixer_checkstyle:
	@${MAKE} csfixer reportformat=checkstyle  dry_run='--dry-run' --no-print-directory > temp/csfixer.checkstyle.xml ;\
	cat temp/csfixer.checkstyle.xml | vendor/bin/cs2pr ;\
	echo ""

csfixer_dry:
	@${MAKE} csfixer   dry_run='--dry-run' --no-print-directory

PHPCS_SENTENCE:=--standard=.phpcs.xml  --parallel=2 --cache=.build/phpcs/php-cs.cache  src

phpcs_reqs:
	$(eval executable:=tools/phpcs)
	$(eval package_name:=phpcs)
	@${MAKE} check_executable_or_exit_with_phive  executable=$(executable) package_name=$(package_name) --no-print-directory 

phpcs: phpcs_reqs
phpcs:
ifeq (,$(reportformat))
	$(eval reportformat='diff')
endif	
	@mkdir -p .build/phpcs && touch .build/phpcs/php-cs.cache ;\
	echo wait... ;\
	$(executable) --report=$(reportformat)  $(PHPCS_SENTENCE)


phpcs_checkstyle: phpcs_reqs
phpcs_checkstyle:
	$(eval reportformat='checkstyle')
	@$(executable) --report=$(reportformat) $(PHPCS_SENTENCE) | grep '<' > temp/phpcs.checkstyle.xml 2>&1; vendor/bin/cs2pr < temp/phpcs.checkstyle.xml


psalm:
	$(eval executable:=vendor/bin/psalm)
	$(eval package_name:=vimeo/psalm)
	@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory 
	@mkdir -p .build/psalm ;\
	echo -e "Running:" ;\
	echo -e "$(GREEN)vendor/bin/psalm$(WHITE) --show-info=false --long-progress --threads=2 --config=psalm.xml "
	@vendor/bin/psalm --show-info=false --long-progress --threads=2 --config=psalm.xml 


		echo -e "Install it with $(GREEN)composer require --dev$(package_name)$(WHITE)" ;\
		exit 1 ;\

phpstan:
	$(eval executable:=vendor/bin/phpstan)
	$(eval package_name:=phpstan/phpstan)
	@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory 
	@mkdir -p .build/phpstan ;\
	echo -e "Running:" ;\
	echo -e "$(GREEN)vendor/bin/phpstan$(WHITE) analyse --memory-limit=2G   --configuration phpstan.neon " 

	@vendor/bin/phpstan analyse --memory-limit=2G   --configuration phpstan.neon 

phpstan_checkstyle:
	@${MAKE} phpstan error-format=checkstyle >  temp/phpstan.checkstyle.xml ;\
	cat temp/phpstan.checkstyle.xml | vendor/bin/cs2pr ;\
	echo ""


rector:
	$(eval executable:=vendor/bin/rector)
	$(eval package_name:=rector/rector)
	@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory 
	@$(executable) process  --ansi --dry-run
lint:
	$(eval executable:=vendor/bin/parallel-lint )
	$(eval package_name:=php-parallel-lint/php-parallel-lint )
	@${MAKE} abort_suggesting_composer executable=$(executable) package_name=$(package_name) --no-print-directory 
	mkdir -p .build/parallel ;\
	$(executable) --ignore-fails --exclude vendor  src 


fixers: dependency_analysis lint csfixer_dry psalm phpstan phpcs  



install_dev_deps:
	@if [ "$(HAS_PHIVE)" == "" ]; then \
		echo -e "$(GREEN)phive$(WHITE) is $(RED)NOT$(WHITE) installed. " ;\
		echo -e "Visit $(GREEN)https://github.com/phar-io/phive$(WHITE) and follow install procedure" ;\
	else \
		 phive install -g --trust-gpg-keys phpmd ;\
		 phive install -g --trust-gpg-keys phpcpd ;\
		 phive install -g --trust-gpg-keys phpcs ;\
		 phive install -g --trust-gpg-keys composer-unused ;\
		 phive install -g --trust-gpg-keys composer-require-checker ;\
		curl -sfL https://raw.githubusercontent.com/reviewdog/reviewdog/master/install.sh | sh -s -- -b tools ;\
	fi ;\
	echo ""


.PHONY: dependency_analysis
dependency_analysis: vendor ## Runs a dependency analysis with maglnet/composer-require-checker
	$(eval executable:=tools/composer-require-checker)
	$(eval package_name:=composer-require-checker )
	@${MAKE} check_executable_or_exit_with_phive executable=$(executable) package_name=$(package_name) --no-print-directory 
	@$(executable) check --config-file=$(shell pwd)/.build/composer-require-checker.json
	tools/composer-unused --excludePackage=adodb/adodb-php



	
reviewdog:
	$(eval executable:=tools/reviewdog)
	$(eval package_name:=reviewdog )
	@if [ ! -f "$(executable)" ]; then \
		echo -e "$(GREEN)$(package_name)$(WHITE) $(RED)NOT FOUND$(WHITE) on $(CYAN)$(executable)$(WHITE). " ;\
		echo -e "Install it with " ;\
		echo -e "curl -sfL https://raw.githubusercontent.com/reviewdog/reviewdog/master/install.sh | sh -s -- -b tools " ;\
		exit 1 ;\
	fi
	@tools/reviewdog -diff="git diff develop"