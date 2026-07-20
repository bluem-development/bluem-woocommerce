.DEFAULT_GOAL := help

-include build.env

PLUGIN_VERSION ?= $(NEW_TAG)
ACCEPTANCE_URL ?= http://localhost:8000
WP_ADMIN_USER ?= wordpress
WP_ADMIN_PASSWORD ?= wordpress
WP_ADMIN_EMAIL ?= wordpress@example.com

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
	@printf '\- make acceptance_translation_test\n'
	@printf '\- make acceptance_smoke_test\n'
	@printf '\- make integration_test\n'
	@printf '\- make add_git_hooks\n'

.PHONY: install
install:
	composer install

.PHONY: translations
translations:
	wp i18n make-pot . languages/bluem.pot --skip-js --domain=bluem --exclude="svn-directory,build,docker,vendor,scripts"
	msgmerge --update --no-fuzzy-matching --backup=none languages/bluem-nl_NL.po languages/bluem.pot
	msgmerge --update --no-fuzzy-matching --backup=none languages/bluem-en_US.po languages/bluem.pot
	msgfmt --check languages/bluem-nl_NL.po -o languages/bluem-nl_NL.mo
	msgfmt --check languages/bluem-en_US.po -o languages/bluem-en_US.mo

.PHONY: lint
lint:
	echo "Ensure that you ran composer install in the tools/php-cs-fixer folder first, otherwise the php-cs-fixer will not be available."
	./tools/php-cs-fixer/vendor/bin/php-cs-fixer check

.PHONY: lint_fix
lint_fix:
	echo "Ensure that you ran composer install in the tools/php-cs-fixer folder first, otherwise the php-cs-fixer will not be available."
	./tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

.PHONY: test
test:
	make unit_test;
	make acceptance_test;

.PHONY: unit_test
unit_test:
	@printf 'Unit tests:\n';
	./vendor/bin/phpunit

.PHONY: acceptance_test
acceptance_test: acceptance_prepare
	@printf 'Acceptance tests:\n';
	php vendor/bin/codecept run --steps

.PHONY: acceptance_prepare
acceptance_prepare: copy-to-docker
	@printf 'Starting Docker WordPress test site:\n';
	docker compose up -d db wordpress
	WP_ACCEPTANCE_URL="$(ACCEPTANCE_URL)" \
	WP_ACCEPTANCE_ADMIN_USER="$(WP_ADMIN_USER)" \
	WP_ACCEPTANCE_ADMIN_PASSWORD="$(WP_ADMIN_PASSWORD)" \
	WP_ACCEPTANCE_ADMIN_EMAIL="$(WP_ADMIN_EMAIL)" \
	bash ./scripts/acceptance-prepare.sh

.PHONY: acceptance_translation_test
acceptance_translation_test: acceptance_prepare
	@printf 'Translation integration tests:\n';
	docker compose run --rm wpcli eval-file /opt/bluem-scripts/acceptance-test-translations.php --allow-root

.PHONY: acceptance_check_site
acceptance_check_site:
	@printf 'Checking WordPress at $(ACCEPTANCE_URL)...\n';
	@curl --silent --show-error --fail --location --max-time 5 "$(ACCEPTANCE_URL)/wp-login.php" > /dev/null || { \
		printf 'WordPress is not reachable at $(ACCEPTANCE_URL). Start and prepare the local Docker site before running acceptance tests.\n'; \
		exit 1; \
	}

.PHONY: acceptance_smoke_test
acceptance_smoke_test: acceptance_prepare
	@printf 'Acceptance smoke tests:\n';
	php vendor/bin/codecept run Acceptance --group smoke --steps

.PHONY: integration_test
integration_test:
	@printf 'HPOS/order-storage integration tests:\n';
	bash ./scripts/run-hpos-integration-test.sh

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

get-fresh-svn:
	svn checkout $(SVN_URL) svn-directory
	# https://plugins.svn.wordpress.org/bluem

svn-check:
	@echo "$(BLUE)Checking SVN availability...$(NC)"
	@svn --version > /dev/null 2>&1 || (echo "$(RED)SVN not available. Please install SVN.$(NC)" && exit 1)

repo-check:
	@echo "$(BLUE)Checking SVN repository availability...$(NC)"
	@svn info $(SVN_URL) > /dev/null 2>&1 || (echo "$(RED)Cannot access SVN repository. Check network or URL.$(NC)" && exit 1)

pre-deployment:
	#make run-phpcbf
	@echo "$(BLUE)Preparing build directory...$(NC)"
	if [ -d "$(BUILD_DIR)" ]; then \
        if [ "$(BUILD_DIR)" != "/" ]; then \
            find "$(BUILD_DIR)" -mindepth 1 -maxdepth 1 -exec rm -rf {} +; \
        fi \
    else \
        mkdir -p "$(BUILD_DIR)"; \
    fi
	@rsync -av --exclude-from='.svnignore' $(CURRENT_DIR)/ $(BUILD_DIR)/
	@echo "$(BLUE)Installing Composer dependencies in build directory...$(NC)"
	@cd $(BUILD_DIR) && composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction || { echo "$(RED)Composer install failed!$(NC)"; exit 1; }
	@cd $(BUILD_DIR) && composer clear-cache
	@echo "$(BLUE)Removing unnecessary files from build directory...$(NC)"
	@cd $(BUILD_DIR) && rm -rf README.md AGENTS.md docs error-report.md .git Makefile tools .env.sample .gitignore Dockerfile .env.sample .gitignore docker-compose.yml codeception.yml Dockerfile loadenv.sh Makefile .php-cs-fixer.cache .php-cs-fixer.dist.php .phpunit.result.cache .travis.yml phpunit.xml psalm.xml .DS_STORE .svnignore .vscode loadenv.sh
	@rm -rf $(BUILD_DIR)/vendor/bluem-development/bluem-php/examples $(BUILD_DIR)/vendor/bluem-development/bluem-php/tests $(BUILD_DIR)/vendor/bluem-development/bluem-php/.github
	@rm -rf $(BUILD_DIR)/vendor/bluem-development/bluem-php/.githooks
	@rm -f $(BUILD_DIR)/vendor/bluem-development/bluem-php/.env.example
	@rm $(BUILD_DIR)/build.env
	@rm -f $(BUILD_DIR)/vendor/bluem-development/bluem-php/.gitignore
	@rm -f $(BUILD_DIR)/vendor/bluem-development/bluem-php/AGENTS.md $(BUILD_DIR)/vendor/bluem-development/bluem-php/Makefile $(BUILD_DIR)/vendor/bluem-development/bluem-php/README.md $(BUILD_DIR)/vendor/bluem-development/bluem-php/changelog.md $(BUILD_DIR)/vendor/bluem-development/bluem-php/composer.json $(BUILD_DIR)/vendor/bluem-development/bluem-php/composer.lock $(BUILD_DIR)/vendor/bluem-development/bluem-php/phpcs.xml $(BUILD_DIR)/vendor/bluem-development/bluem-php/phpcs.xml.dist $(BUILD_DIR)/vendor/bluem-development/bluem-php/phpunit.xml $(BUILD_DIR)/vendor/bluem-development/bluem-php/rector.php
	@rm -f $(BUILD_DIR)/vendor/robrichards/xmlseclibs/CHANGELOG.txt $(BUILD_DIR)/vendor/robrichards/xmlseclibs/README.md $(BUILD_DIR)/vendor/robrichards/xmlseclibs/composer.json $(BUILD_DIR)/vendor/robrichards/xmlseclibs/phpunit.xml
	@rm -rf $(BUILD_DIR)/vendor/robrichards/xmlseclibs/.github
	@rm -rf $(BUILD_DIR)/vendor/selective/xmldsig/.github

add-tag:
	make check-tag
	@echo "$(BLUE)Copying files to SVN tag directory...$(NC)"
	@echo "Folder: $(SVN_DIR)/tags/$(PLUGIN_VERSION)"
	if [ -d "$(SVN_DIR)/tags/$(PLUGIN_VERSION)" ]; then \
		find "$(SVN_DIR)/tags/$(PLUGIN_VERSION)" -mindepth 1 -maxdepth 1 -exec rm -rf {} +; \
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
		find "$(SVN_DIR)/trunk" -mindepth 1 -maxdepth 1 -exec rm -rf {} +; \
	else \
		mkdir -p "$(SVN_DIR)/trunk"; \
	fi
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

.PHONY: release
release: commit-to-svn

clean-up:
	@echo "$(BLUE)Cleaning up...$(NC)"
	@#rm -rf $(BUILD_DIR)

copy-to-docker:
	make pre-deployment;
	@echo "$(BLUE)Copying all from build to docker/plugins/bluem directory...$(NC)"
	# create docker/plugins/bluem if it doesn't exist
	if [ ! -d "./docker/plugins/bluem" ]; then \
		mkdir -p "./docker/plugins/bluem"; \
	fi
	cp -r ./build/* ./docker/plugins/bluem/.


run-phpcs:
	vendor/bin/phpcs --standard=WordPress ./src bluem-*.php

run-phpcbf:
	vendor/bin/phpcbf --standard=WordPress ./src bluem-*.php

start-docker:
	docker-compose up -d
	sleep 1
	open http://localhost:8000/wp-admin/
