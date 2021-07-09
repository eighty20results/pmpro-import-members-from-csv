###
# Plugin specific settings for Makefile - You may need to change information
# in the included file!
###
include build_config/plugin_config.mk

###
# Standard settings for Makefile - Probably won't need to change anything here
###
SHELL := /bin/bash
BASE_PATH := $(PWD)
FIND := $(shell which find)
CURL := $(shell which curl)
UNZIP := $(shell which unzip)
PHP_BIN := $(shell which php)
DC_BIN := $(shell which docker-compose)
SQL_BACKUP_FILE ?= $(PWD)/.circleci/docker/test/db_backup
MYSQL_DATABASE ?= wordpress
MYSQL_USER ?= wordpress
MYSQL_PASSWORD ?= wordpress
DB_IMAGE ?= mariadb
WORDPRESS_DB_HOST ?= localhost
WP_PLUGIN_URL ?= "https://downloads.wordpress.org/plugin/"
E20R_PLUGIN_URL ?= "https://eighty20results.com/protected-content"
WP_CONTAINER_NAME ?= codecep-wp-$(E20R_PLUGIN_NAME)
DB_CONTAINER_NAME ?= $(DB_IMAGE)-wp-$(E20R_PLUGIN_NAME)
CONTAINER_ACCESS_TOKEN := $(shell if [[ -f ./docker.hub.key ]]; then cat ./docker.hub.key ; fi)

CONTAINER_REPO ?= 'docker.io/$(DOCKER_USER)'
DOCKER_IS_RUNNING := $(shell ps -ef | grep Docker.app | wc -l | xargs)

ifeq ($(CONTAINER_ACCESS_TOKEN),)
CONTAINER_ACCESS_TOKEN := $(shell echo "$${CONTAINER_ACCESS_TOKEN}" )
endif
DOWNLOAD_MODULE := 1

# Determine if there is a local (to this system) instance of the E20R Utilities module repository
ifneq ($(wildcard $(E20R_UTILITIES_PATH)/src/licensing/class-licensing.php), "")
	DOWNLOAD_MODULE := $(shell grep -q 'public function __construct' $(E20R_UTILITIES_PATH)/src/licensing/class-licensing.php && echo "0")
endif

$(info Download the E20R Utilities module: $(DOWNLOAD_MODULE))

#ifeq ($(CONTAINER_ACCESS_TOKEN),)
#	echo "Error: Docker login token is not defined!"
#	exit 1
#endif

# PROJECT := $(shell basename ${PWD}) # This is the default as long as the plugin name matches
PROJECT := $(E20R_PLUGIN_NAME)
VOLUME_CONTAINER ?= $(PROJECT)_volume

# Settings for docker-compose
DC_CONFIG_FILE ?= $(PWD)/docker-compose.yml
DC_ENV_FILE ?= $(PWD)/tests/_envs/.env.testing

STACK_RUNNING := $(shell APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
    		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) ps -q 2> /dev/null | wc -l)

.PHONY: \
	docs \
	readme \
	changelog \
	metadata \
	git-log \
	clean \
	clean-inc \
	clean-wp-deps \
	real-clean \
	wp-deps \
	e20r-deps \
	is-docker-running \
	docker-deps \
	docker-compose-deps \
	start-stack \
	stop-stack \
	restart \
	shell \
	lint-test \
	code-standard-test \
	phpstan-test \
	wp-unit-test \
	acceptance-test \
	build-test \
	new-release \
	wp-shell \
	wp-log \
	db-shell \
	db-backup \
	db-import \
	test \
	image-build \
	image-pull \
	image-push \
	image-scan \
	repo-login

clean:
	@if [[ -n "$(STACK_RUNNING)" ]]; then \
		if [[ -f $(COMPOSER_DIR)/bin/codecept ]]; then \
			$(COMPOSER_DIR)/bin/codecept clean ; \
		fi ; \
	fi
	@rm -rf _actions/
	@rm -rf workflow

clean-inc:
	@find $(COMPOSER_DIR)/* -type d -maxdepth 0 -exec rm -rf {} \; && rm $(COMPOSER_DIR)/*.php

repo-login:
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker login --username $(DOCKER_USER) --password-stdin <<< $(CONTAINER_ACCESS_TOKEN)

image-build: docker-deps
	@echo "Building the docker container stack for $(PROJECT)"
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) build --pull --progress tty

image-scan: repo-login
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker scan --accept-license $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

image-push: repo-login # image-scan - TODO: Enable image-scan if we can get the issues fixed
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
		docker tag $(PROJECT)_wordpress $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)
	@APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
  		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
    	docker push $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION)

image-pull: repo-login
	@echo "Pulling image from Docker repo"
	@if docker manifest inspect $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION) > /dev/null; then \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
      		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
        	docker pull $(CONTAINER_REPO)/$(PROJECT)_wordpress:$(WP_IMAGE_VERSION); \
     fi

real-clean: stop-stack clean clean-inc clean-wp-deps
	@echo "Make sure docker-compose stack for $(PROJECT) isn't running"
	@echo "Stack is running: $(STACK_RUNNING)"
	@if [[ 2 -ne "$(STACK_RUNNING)" ]]; then \
		echo "Stopping docker-compose stack" ; \
		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) rm --stop --force -v ; \
	fi ; \
	echo "Removing docker images" && \
	docker image remove $(PROJECT)_wordpress --force

php-composer:
	@if [[ -n "$(PHP_BIN)" ]]; then \
	    echo "Install the PHP Composer component" && \
	    $(PHP_BIN) -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
	    $(PHP_BIN) -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
            $(PHP_BIN) composer-setup.php --install-dir=$(PWD)/ && \
            $(PHP_BIN) -r "unlink('composer-setup.php');" ; \
    fi

composer-prod: composer.json real-clean clean-inc php-composer
	@echo "Install/Update the Production composer dependencies"
	@$(PHP_BIN) $(COMPOSER_BIN) update --ansi --prefer-stable --no-dev

composer-dev: composer.json php-composer
	@echo "Use composer to install/update the PHP test dependencies"
	@$(PHP_BIN) $(COMPOSER_BIN) update --ansi --prefer-stable

docker-compose:
	@if [[ -z "$(DC_BIN)" && ! -f /usr/local/bin/docker-compose ]]; then \
		echo "Installing docker-compose" && \
		sudo curl --silent -L https://github.com/docker/compose/releases/download/$(COMPOSER_VERSION)/docker-compose-`uname -s`-`uname -m` \
			-o /usr/local/bin/docker-compose && \
		sudo chmod +x /usr/local/bin/docker-compose ; \
	fi

clean-wp-deps:
	@rm -rf $(COMPOSER_DIR)/wp_plugins/*

# git archive --prefix="$${e20r_plugin}/" --format=zip --output="$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" --worktree-attributes main &&

e20r-deps:
	@echo "Loading defined E20R custom plugin dependencies"
	@mkdir -p $(COMPOSER_DIR)/wp_plugins
	@for e20r_plugin in $(E20R_DEPENDENCIES) ; do \
		echo "Checking for presence of $${e20r_plugin}..." ; \
		if [[ ! -f "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}/*.php" ]]; then \
			echo "Download or build $${e20r_plugin}.zip dependency?" && \
			if [[ "0" -eq "$(DOWNLOAD_MODULE)" && "00-e20r-utilities" -eq "$${e20r_plugin}" ]]; then \
				echo "Build $${e20r_plugin} archive and save to $(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" && \
				cd $(E20R_UTILITIES_PATH) && \
				make build && \
				new_kit="$$(ls -art build/kits/$${e20r_plugin}* | tail -1)" && \
				echo "Copy $${new_kit} to $(BASE_PATH)/$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" && \
				cp "$${new_kit}" "$(BASE_PATH)/$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" && \
				cd $(BASE_PATH) ; \
			else \
				echo "Download $${e20r_plugin} to $(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" && \
				$(CURL) -L "$(E20R_PLUGIN_URL)/$${e20r_plugin}.zip" -o "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" ; \
			fi ; \
			mkdir -p "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}" && \
			echo "Installing the $${e20r_plugin}.zip plugin" && \
			$(UNZIP) -o "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" -d $(COMPOSER_DIR)/wp_plugins/ 2>&1 > /dev/null && \
			rm -f "$(COMPOSER_DIR)/wp_plugins/$${e20r_plugin}.zip" ; \
		fi ; \
	done

is-docker-running:
	@if [[ "0" -eq $(DOCKER_IS_RUNNING) ]]; then \
		echo "Error: Docker is not running on this system!" && \
		exit 1; \
	fi

docker-deps: is-docker-running docker-compose wp-deps

wp-deps: clean composer-dev e20r-deps
	@echo "Loading WordPress plugin dependencies"
	@for dep_plugin in $(WP_DEPENDENCIES) ; do \
  		if [[ ! -d "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}" ]]; then \
  		  echo "Download and install $${dep_plugin} to $(COMPOSER_DIR)/wp_plugins/$${dep_plugin}" && \
  		  mkdir -p "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}" && \
  		  $(CURL) -L "$(WP_PLUGIN_URL)/$${dep_plugin}.zip" -o "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}.zip" -s && \
  		  $(UNZIP) -o "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}.zip" -d $(COMPOSER_DIR)/wp_plugins/ 2>&1 > /dev/null && \
  		  rm -f "$(COMPOSER_DIR)/wp_plugins/$${dep_plugin}.zip" ; \
  		fi ; \
  	done

start-stack: docker-deps image-pull
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@echo "Current directory: $(shell pwd)"
	@if [[ 2 -ne "$(STACK_RUNNING)" ]]; then \
  		echo "Building and starting the WordPress stack for testing purposes" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
			docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) up --detach ; \
	fi

db-import: start-stack
	@echo "Maybe load WordPress data...?"
	@bin/wait-for-db.sh '$(MYSQL_USER)' '$(MYSQL_PASSWORD)' '$(WORDPRESS_DB_HOST)' '$(E20R_PLUGIN_NAME)'
	@if [[ -f "$(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql" ]]; then \
  		echo "Loading WordPress data to use for testing $(E20R_PLUGIN_NAME)"; \
  		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
			DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
	  		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
        		exec -T database \
        		/usr/bin/mysql -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) < $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql; \
  	fi

stop-stack:
	@echo "Number of running containers for $(PROJECT): $(STACK_RUNNING)"
	@if [[ 0 -lt "$(STACK_RUNNING)" ]]; then \
  		echo "Stopping the $(PROJECT) WordPress stack" ; \
		APACHE_RUN_USER=$(APACHE_RUN_USER) APACHE_RUN_GROUP=$(APACHE_RUN_GROUP) \
        		DB_IMAGE=$(DB_IMAGE) DB_VERSION=$(DB_VERSION) WP_VERSION=$(WP_VERSION) VOLUME_CONTAINER=$(VOLUME_CONTAINER) \
        		docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) down 2>/dev/null ; \
	fi


restart: stop-stack db-import

wp-shell:
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec wordpress /bin/bash

wp-log:
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) logs -f wordpress

db-shell:
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database /bin/bash

db-backup:
	docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) exec database \
 		/usr/bin/mysqldump -u$(MYSQL_USER) -p'$(MYSQL_PASSWORD)' -h$(WORDPRESS_DB_HOST) $(MYSQL_DATABASE) > $(SQL_BACKUP_FILE)/$(E20R_PLUGIN_NAME).sql


phpstan-test: composer-dev
	@echo "Loading the PHP-Stan tests for $(PROJECT)"
	@inc/bin/phpstan analyze \
		--ansi \
		--debug \
		-v \
		--configuration=./phpstan.dist.neon \
		--no-interaction \
		--memory-limit=-1

code-standard-test: wp-deps
	@echo "Running WP Code Standards testing for $(PROJECT)"
	@$(COMPOSER_DIR)/bin/phpcs \
		--runtime-set ignore_warnings_on_exit true \
		--report=full \
		--colors \
		-p \
		--standard=WordPress-Extra \
		--ignore='$(PHP_IGNORE_PATHS)' \
		--extensions=php \
		$(PHP_CODE_PATHS)

unit-test: wp-deps
	@echo "Running Unit tests for $(PROJECT)"
# TODO: Add coverage
	@$(COMPOSER_DIR)/bin/codecept run -v --debug unit

wp-unit-test: docker-deps start-stack db-import
	# TODO: Add coverage
	@docker-compose --project-name $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
		exec -T -w /var/www/html/wp-content/plugins/$(PROJECT)/ \
		wordpress $(COMPOSER_DIR)/bin/codecept run -v wpunit

acceptance-test: docker-deps start-stack db-import
	@docker-compose $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 wordpress $(COMPOSER_DIR)/bin/codecept run -v acceptance

build-test: docker-deps start-stack db-import
	@docker-compose $(PROJECT) --env-file $(DC_ENV_FILE) --file $(DC_CONFIG_FILE) \
	 exec -T -w /var/www/html/wp-content/plugins/${PROJECT}/ \
	 wordpress $(PWD)/$(COMPOSER_DIR)/bin/codecept build -v

test: clean wp-deps code-standard-test unit-test start-stack db-import wp-unit-test stop-stack # TODO: phpstan-test between phpcs & unit tests

git-log:
	@./bin/create_log.sh

metadata:
	@./bin/metadata.sh

changelog: build_readmes/current.txt
	@./bin/changelog.sh

readme: changelog # metadata
	@./bin/readme.sh

$(E20R_PLUGIN_BASE_FILE): test stop-stack clean-inc composer-prod
	@if [[ -z "$${USE_LOCAL_BUILD}" ]]; then \
  		E20R_PLUGIN_NAME=$(E20R_PLUGIN_NAME) ./bin/build-plugin.sh ; \
	else \
		rm -rf $(COMPOSER_DIR)/wp_plugins && \
		mkdir -p build/kits/ && \
		E20R_PLUGIN_VERSION=$$(./bin/get_plugin_version.sh $(E20R_PLUGIN_NAME)) \
		git archive --prefix=$(E20R_PLUGIN_NAME)/ --format=zip --output=build/kits/$(E20R_PLUGIN_NAME)-$${E20R_PLUGIN_VERSION}.zip --worktree-attributes main ; \
	fi

build: $(E20R_PLUGIN_BASE_FILE)
	@echo "Built kit for $(E20R_PLUGIN_NAME)"

#new-release: test composer-prod
#	@./build_env/get_version.sh && \
#		git tag $${VERSION} && \
#		./build_env/create_release.sh

docs: git-log readme
