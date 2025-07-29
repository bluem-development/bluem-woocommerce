.DEFAULT_GOAL := help

-include build.env

PLUGIN_VERSION ?= $(NEW_TAG)

plugin_version:
	echo "Version is $(PLUGIN_VERSION)"

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
prepare-release: check-tag confirm svn-check repo-check pre-deployment add-tag update-trunk
# send-email

PLUGIN_VERSION ?= $(NEW_TAG)

check-tag:
	@if [ -z "$(PLUGIN_VERSION)" ]; then \
		echo "$(RED)PLUGIN_VERSION is not set. Use make release NEW_TAG=x.y.z to specify the tag explicitly$(NC)"; \
		exit 1; \
	else \
		  echo "Tag set to $(PLUGIN_VERSION)"; \
	fi

confirm:
	@echo "$(BLUE)You are about to release a new version, namely \"$(PLUGIN_VERSION)\". Are you sure? [Y/n]$(NC)" && read ans && [ $${ans:-Y} = Y ]

svn-check:
	@#echo "$(BLUE)Checking SVN availability...$(NC)"
	@#svn --version > /dev/null 2>&1 || (echo "$(RED)SVN not available. Please install SVN.$(NC)" && exit 1)

repo-check:
	@#echo "$(BLUE)Checking SVN repository availability...$(NC)"
	@#svn info $(SVN_URL) > /dev/null 2>&1 || (echo "$(RED)Cannot access SVN repository. Check network or URL.$(NC)" && exit 1)

pre-deployment:
	#make run-phpcbf
	@npm run build
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
	@cd $(BUILD_DIR) && rm -rf README.md .git Makefile tools .env.sample .gitignore Dockerfile .env.sample .gitignore docker-compose.yml codeception.yml Dockerfile loadenv.sh Makefile .php-cs-fixer.cache .phpunit.result.cache .travis.yml phpunit.xml psalm.xml .DS_STORE .svnignore loadenv.sh package.json package-lock.json
	@rm -rf $(BUILD_DIR)/vendor/bluem-development/bluem-php/examples $(BUILD_DIR)/vendor/bluem-development/bluem-php/tests $(BUILD_DIR)/vendor/bluem-development/bluem-php/.github
	@rm $(BUILD_DIR)/vendor/bluem-development/bluem-php/.env.example
	@rm $(BUILD_DIR)/build.env
	@rm $(BUILD_DIR)/vendor/bluem-development/bluem-php/.gitignore
	@rm -rf $(BUILD_DIR)/vendor/selective/xmldsig/.github

add-tag:
	make check-tag
	@echo "$(BLUE)Copying files to SVN tag directory...$(NC)"
	@echo "Folder: $(SVN_DIR)/tags/$(PLUGIN_VERSION)"
	if [ -d "$(SVN_DIR)/tags/$(PLUGIN_VERSION)" ]; then \
		rm -rf "$(SVN_DIR)/tags/$(PLUGIN_VERSION)"/*; \
	else \
		mkdir -p "$(SVN_DIR)/tags/$(PLUGIN_VERSION)"; \
	fi
	@mkdir -p $(SVN_DIR)/tags/$(PLUGIN_VERSION)
	@cp -R $(BUILD_DIR)/ $(SVN_DIR)/tags/$(PLUGIN_VERSION)/

#add-tag-to-svn:
#	@echo "$(BLUE)Adding new tag $(PLUGIN_VERSION) to SVN repository...$(NC)"
#	@#cd $(SVN_DIR)/tags/$(PLUGIN_VERSION) && svn add --force * --auto-props --parents --depth infinity -q

#svn-commit:
#	@echo "$(BLUE)Committing new tag $(PLUGIN_VERSION) to SVN repository...$(NC)"
#	@#cd $(SVN_DIR)/tags/$(PLUGIN_VERSION) && svn commit -m "Tagging version $(PLUGIN_VERSION)"

update-trunk:
	@echo "$(BLUE)Also updating trunk files to  latest tag $(PLUGIN_VERSION)...$(NC)"
	if [ -d "$(SVN_DIR)/trunk" ]; then \
		rm -rf "$(SVN_DIR)/trunk"/*; \
	else \
		mkdir -p "$(SVN_DIR)/trunk"; \
	fi
	@rm -rf $(SVN_DIR)/trunk/*
	@cp -R $(BUILD_DIR)/* $(SVN_DIR)/trunk/.
	@echo "$(BLUE)Commit trunk to SVN to this latest tag $(PLUGIN_VERSION)...$(NC)"
	@echo "$(RED) Don't forget to actually commit to SVN now."

#	@cd $(SVN_DIR)/trunk && svn add --force * --auto-props --parents --depth infinity -q
#	@cd $(SVN_DIR)/trunk && svn commit -m "Updating trunk to version $(PLUGIN_VERSION)"

commit-to-svn:
	@echo "$(BLUE)Committing tag to SVN...$(NC)"
	svn add $(SVN_DIR)/tags/$(PLUGIN_VERSION) --force
	cd $(SVN_DIR); svn commit -m "Added tags/$(PLUGIN_VERSION)"
	@echo "$(BLUE)Committing trunk to SVN...$(NC)"
	#svn delete $(SVN_DIR)/trunk
	svn add $(SVN_DIR)/trunk --force
	cd $(SVN_DIR); svn commit -m "Replaced trunk folder with version $(PLUGIN_VERSION)"
	@echo "$(GREEN)Done!$(NC)"

get-fresh-svn:
	svn checkout $(SVN_URL) svn-directory
	# https://plugins.svn.wordpress.org/bluem

.PHONY: release
release: commit-to-svn

clean-up:
	@echo "$(BLUE)Cleaning up...$(NC)"
	@#rm -rf $(BUILD_DIR)

copy-to-docker:
	make pre-deployment;
	@echo "$(BLUE)Preparing `docker/plugins/bluem` directory...$(NC)"
	if [ -d "docker/plugins/bluem" ]; then \
        if [ "docker/plugins/bluem" != "/" ]; then \
            rm -rf "docker/plugins/bluem"/*; \
        fi \
    else \
        mkdir -p "docker/plugins/bluem"; \
    fi; \
    cp -r build/* docker/plugins/bluem


run-phpcs:
	vendor/bin/phpcs --standard=WordPress ./src bluem-*.php

run-phpcbf:
	vendor/bin/phpcbf --standard=WordPress ./src bluem-*.php

#send-email:
#	@./loadenv.sh
