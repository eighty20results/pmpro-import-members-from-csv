E20R_PLUGIN_NAME ?= pmpro-import-members-from-csv
E20R_PLUGIN_BASE_FILE ?= class.pmpro-import-members.php
COMPOSER_CHECKSUM := '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8'

ifeq ($(E20R_DEPLOYMENT_SERVER),"")
E20R_DEPLOYMENT_SERVER ?= eighty20results.com
endif

WP_DEPENDENCIES ?= paid-memberships-pro
E20R_DEPENDENCIES ?= 00-e20r-utilities
E20R_UTILITIES_PATH ?= ~/PhpStormProjects/Utilities

DOCKER_HUB_USER ?= eighty20results
DOCKER_ENV ?= Docker.app
DOCKER_IS_RUNNING := $(shell ps -ef | grep $(DOCKER_ENV) | wc -l | xargs)

COMPOSER_VERSION ?= 1.29.2
# COMPOSER_BIN := $(shell which composer)
COMPOSER_BIN := composer.phar
COMPOSER_DIR := inc

APACHE_RUN_USER ?= $(shell id -u)
# APACHE_RUN_GROUP ?= $(shell id -g)
APACHE_RUN_GROUP ?= $(shell id -u)

WP_VERSION ?= latest
DB_VERSION ?= latest
WP_IMAGE_VERSION ?= 1.0

PHP_CODE_PATHS := *.php src/E20R/**/*.php src/E20R/**/**/*.php src/E20R/**/**/**/*.php
PHP_IGNORE_PATHS := $(COMPOSER_DIR)/*,node_modules/*,src/utilities/*
