.PHONY: install lint-check lint test

install:
	@echo "Installing dependencies..."
	@composer install

lint-check:
	@echo "Checking code style..."
	@php vendor/bin/php-cs-fixer fix --diff --dry-run --show-progress=none

lint:
	@echo "Fixing code style..."
	@php vendor/bin/php-cs-fixer fix --diff --show-progress=none

test:
	@echo "Running tests..."
	@php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml
