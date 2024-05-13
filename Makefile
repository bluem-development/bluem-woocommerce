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
NC = \033[0m # No Color


.PHONY: release

release: check-tag confirm svn-check repo-check pre-deployment add-tag svn-commit clean-up send-email

check-tag:
	@if [ -z "$(NEW_TAG)" ]; then \
		echo "$(RED)NEW_TAG is not set. Use make release NEW_TAG=x.y.z to specify the tag$(NC)"; \
		exit 1; \
	fi

confirm:
	@echo "$(GREEN)You are about to release a new version: $(NEW_TAG). Are you sure? [Y/n]$(NC)" && read ans && [ $${ans:-Y} = Y ]

svn-check:
	@echo "$(GREEN)Checking SVN availability...$(NC)"
	@svn --version > /dev/null 2>&1 || (echo "$(RED)SVN not available. Please install SVN.$(NC)" && exit 1)

repo-check:
	@echo "$(GREEN)Checking SVN repository availability...$(NC)"
	@svn info $(SVN_URL) > /dev/null 2>&1 || (echo "$(RED)Cannot access SVN repository. Check network or URL.$(NC)" && exit 1)

pre-deployment:
	@echo "$(GREEN)Preparing build directory...$(NC)"
	@mkdir -p $(BUILD_DIR)
	@rsync -av --exclude='build/' $(CURRENT_DIR)/ $(BUILD_DIR)/
	@echo "$(GREEN)Installing Composer dependencies in build directory...$(NC)"
	@cd $(BUILD_DIR) && composer install --no-dev --optimize-autoloader
	@echo "$(GREEN)Removing unnecessary files from build directory...$(NC)"
	@cd $(BUILD_DIR) && rm -rf .git composer.* Makefile tools

add-tag:
	@echo "$(GREEN)Copying files to SVN tag directory...$(NC)"
	@mkdir -p $(SVN_DIR)/tags/$(NEW_TAG)
	@cp -R $(BUILD_DIR)/ $(SVN_DIR)/tags/$(NEW_TAG)/
	@cd $(SVN_DIR)/tags/$(NEW_TAG) && svn add --force * --auto-props --parents --depth infinity -q

svn-commit:
	@echo "$(GREEN)Committing new tag $(NEW_TAG) to SVN repository...$(NC)"
	@cd $(SVN_DIR)/tags/$(NEW_TAG) && svn commit -m "Tagging version $(NEW_TAG)"

clean-up:
	@echo "$(GREEN)Cleaning up...$(NC)"
	@rm -rf $(BUILD_DIR)

#send-email:
#	@./loadenv.sh
