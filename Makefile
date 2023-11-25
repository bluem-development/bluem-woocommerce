.DEFAULT_GOAL := help

.PHONY: help
help:
	@printf '\nTo run a task: make <task_name>\n'
	@printf '\nExamples:\n'
	@printf '\- make test\n'
	@printf '\- make unit_test\n'
	@printf '\- make acceptance_test\n'
	@printf '\- make add_git_hooks\n'

.PHONY: test
test: unit_test acceptance_test

.PHONY: unit_test
unit_test:
	./vendor/bin/phpunit

.PHONY: acceptance_test
acceptance_test:
	php vendor/bin/codecept run --steps

.PHONY: add_git_hooks
add_git_hooks:
	sh ./scripts/add-git-hooks.sh

