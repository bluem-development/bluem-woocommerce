.DEFAULT_GOAL := help

.PHONY: help
help:
	@printf '\nTo run a task: make <task_name>\n'
	@printf '\nExamples:\n'
	@printf '\- make install\n'
	@printf '\- make test\n'
	@printf '\- make unit_test\n'
	@printf '\- make acceptance_test\n'
	@printf '\- make add_git_hooks\n'

.PHONY: install
install:
	composer install

.PHONY: lint
lint:
	./tools/php-cs-fixer/vendor/bin/php-cs-fixer check .

.PHONY: lint_fix
lint_fix:
	./tools/php-cs-fixer/vendor/bin/php-cs-fixer fix .

.PHONY: test
test:
	make unit_test;
	make acceptance_test;

.PHONY: unit_test
unit_test:
	@printf 'Unit tests:\n';
	./vendor/bin/phpunit

.PHONY: acceptance_test
acceptance_test:
	@printf 'Acceptance tests:\n';
	php vendor/bin/codecept run --steps

.PHONY: add_git_hooks
add_git_hooks:
	sh ./scripts/add-git-hooks.sh

# Releasing a new version of a WordPress plugin

# https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#starting-a-new-plugin

# ensure svn is installed
# with brew install svn

# Makefile for releasing a new version of a WordPress plugin

PLUGIN_SLUG = bluem
SVN_URL = https://plugins.svn.wordpress.org/$(PLUGIN_SLUG)
SVN_DIR = svn-directory
CURRENT_DIR = $(shell pwd)
BUILD_DIR = $(CURRENT_DIR)/build

EMAIL = pluginsupport@bluem.nl

# Colors for terminal output
RED = \033[0;31m
GREEN = \033[0;32m
BLUE   => \033[34m
NC = \033[0m # No Color


.PHONY: prepare-release
prepare-release: check-tag confirm svn-check repo-check pre-deployment add-tag update-trunk clean-up
# send-email

check-tag:
	@if [ -z "$(NEW_TAG)" ]; then \
		echo "$(RED)NEW_TAG is not set. Use make release NEW_TAG=x.y.z to specify the tag$(NC)"; \
		exit 1; \
	fi

confirm:
	@echo "$(BLUE)You are about to release a new version, namely \"$(NEW_TAG)\". Are you sure? [Y/n]$(NC)" && read ans && [ $${ans:-Y} = Y ]

svn-check:
	@#echo "$(BLUE)Checking SVN availability...$(NC)"
	@#svn --version > /dev/null 2>&1 || (echo "$(RED)SVN not available. Please install SVN.$(NC)" && exit 1)

repo-check:
	@#echo "$(BLUE)Checking SVN repository availability...$(NC)"
	@#svn info $(SVN_URL) > /dev/null 2>&1 || (echo "$(RED)Cannot access SVN repository. Check network or URL.$(NC)" && exit 1)

pre-deployment:
	#make run-phpcbf
	@echo "$(BLUE)Preparing build directory...$(NC)"
	if [ -d "$(BUILD_DIR)" ]; then \
        if [ "$(BUILD_DIR)" != "/" ]; then \
            rm -rf "$(BUILD_DIR)"/*; \
        fi \
    else \
        mkdir -p "$(BUILD_DIR)"; \
    fi
	@rsync -av --exclude-from='.svnignore' $(CURRENT_DIR)/ $(BUILD_DIR)/
	@echo "$(BLUE)Installing Composer dependencies in build directory...$(NC)"
	@cd $(BUILD_DIR) && composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction || { echo "$(RED)Composer install failed!$(NC)"; exit 1; }
	@cd $(BUILD_DIR) && composer clear-cache
	@echo "$(BLUE)Removing unnecessary files from build directory...$(NC)"
	@cd $(BUILD_DIR) && rm -rf README.md .git Makefile tools .env.sample .gitignore Dockerfile .env.sample .gitignore docker-compose.yml codeception.yml Dockerfile loadenv.sh Makefile .php-cs-fixer.cache .phpunit.result.cache .travis.yml phpunit.xml psalm.xml .DS_STORE .svnignore loadenv.sh
	@rm -rf $(BUILD_DIR)/vendor/bluem-development/bluem-php/examples $(BUILD_DIR)/vendor/bluem-development/bluem-php/tests $(BUILD_DIR)/vendor/bluem-development/bluem-php/.github
	@rm $(BUILD_DIR)/vendor/bluem-development/bluem-php/.env.example
	@rm $(BUILD_DIR)/vendor/bluem-development/bluem-php/.gitignore
	@rm -rf $(BUILD_DIR)/vendor/selective/xmldsig/.github

add-tag:
	@echo "$(BLUE)Copying files to SVN tag directory...$(NC)"
	@echo "Folder: $(SVN_DIR)/tags/$(NEW_TAG)"
	if [ -d "$(SVN_DIR)/tags/$(NEW_TAG)" ]; then \
		rm -rf "$(SVN_DIR)/tags/$(NEW_TAG)"/*; \
	else \
		mkdir -p "$(SVN_DIR)/tags/$(NEW_TAG)"; \
	fi
	@mkdir -p $(SVN_DIR)/tags/$(NEW_TAG)
	@cp -R $(BUILD_DIR)/ $(SVN_DIR)/tags/$(NEW_TAG)/

#add-tag-to-svn:
#	@echo "$(BLUE)Adding new tag $(NEW_TAG) to SVN repository...$(NC)"
#	@#cd $(SVN_DIR)/tags/$(NEW_TAG) && svn add --force * --auto-props --parents --depth infinity -q

#svn-commit:
#	@echo "$(BLUE)Committing new tag $(NEW_TAG) to SVN repository...$(NC)"
#	@#cd $(SVN_DIR)/tags/$(NEW_TAG) && svn commit -m "Tagging version $(NEW_TAG)"

update-trunk:
	@echo "$(BLUE)Also updating trunk files to  latest tag $(NEW_TAG)...$(NC)"
	if [ -d "$(SVN_DIR)/trunk" ]; then \
		rm -rf "$(SVN_DIR)/trunk"/*; \
	else \
		mkdir -p "$(SVN_DIR)/trunk"; \
	fi
	@rm -rf $(SVN_DIR)/trunk/*
	@cp -R $(BUILD_DIR)/* $(SVN_DIR)/trunk/.
	@echo "$(BLUE)Commit trunk to SVN to this latest tag $(NEW_TAG)...$(NC)"
	@echo "$(RED) Don't forget to actually commit to SVN now."

#	@cd $(SVN_DIR)/trunk && svn add --force * --auto-props --parents --depth infinity -q
#	@cd $(SVN_DIR)/trunk && svn commit -m "Updating trunk to version $(NEW_TAG)"

commit-to-svn:
	svn add $(SVN_DIR)/tags/$(NEW_TAG)
	cd $(SVN_DIR); svn commit -m "Added tags/$(NEW_TAG)"
	svn delete $(SVN_DIR)/trunk
	svn add $(SVN_DIR)/trunk --force
	cd $(SVN_DIR); svn commit -m "Replaced trunk folder with version $(NEW_TAG)"

get-fresh-svn:
	svn checkout $(SVN_URL) svn-directory
	# https://plugins.svn.wordpress.org/bluem

.PHONY: release
release: commit-to-svn

clean-up:
	@echo "$(BLUE)Cleaning up...$(NC)"
	@#rm -rf $(BUILD_DIR)

copy-to-docker:
	make pre-deployment; cp -r build/* ../plugins/bluem/.


run-phpcs:
	vendor/bin/phpcs --standard=WordPress ./src bluem-*.php

run-phpcbf:
	vendor/bin/phpcbf --standard=WordPress ./src bluem-*.php

#send-email:
#	@./loadenv.sh
