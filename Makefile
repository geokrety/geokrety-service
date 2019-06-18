# started from https://gist.github.com/mpneuried/0594963ad38e68917ef189b4e6a269db
#######
# import config.
# You can change the default config with `make cnf="config_special.env" build`
cnf ?= config.env
include $(cnf)
export $(shell sed 's/=.*//' $(cnf))

ifeq "$(OS)" "Windows_NT"
	PREFIX=winpty
else
	PREFIX=
endif

# HELP
# This will output the help for each task
# thanks to https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
.PHONY: help

help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# DOCKER TASKS
# Build the container
build: ## Build the container
	docker build -t $(APP_NAME) .

build-nc: ## Build the container without caching
	docker build --no-cache -t $(APP_NAME) .

run: ## Run container on port configured in `config.env`
	docker run -d --env-file=./config.env -p=$(HOST_PORT):$(CONTAINER_PORT) --name="$(APP_NAME)" $(APP_NAME)

up: build run ## Alias to build and run

stop: ## Stop a running container
	docker stop $(APP_NAME)

start: ## Start stopped container
	docker start $(APP_NAME)

remove: ## Remove container
	docker rm $(APP_NAME)

stoprm: stop remove ## Alias to stop and remove

sh: ## container shell
	${PREFIX} docker exec -it ${APP_NAME} bash

tail: ## tail -f app logs
	@echo "${APP_NAME} logs (ctr+c to quit)"
	docker logs -f ${APP_NAME}

copy: ## copy host resources to the running container and restart cron and composer
	@echo " - www"
	docker cp www/. ${APP_NAME}:/var/www/html
	@echo " - resources"
	docker cp resources/. ${APP_NAME}:/opt/geokrety
	@echo " - src"
	docker cp src/. ${APP_NAME}:/opt/geokrety
	@echo " - cron config"
	docker cp resources/geokrety-crontab ${APP_NAME}:/etc/cron.d/geokrety-cron
	@echo " - cron reload"
	${PREFIX} docker exec -it ${APP_NAME} service cron reload
	@echo " - composer install"
	${PREFIX} docker exec -it ${APP_NAME} composer install
