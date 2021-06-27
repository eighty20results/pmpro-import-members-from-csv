FROM debian:stable-slim

ENV BUILD_DIR /build_env
ENV BASE_DIR /

RUN mkdir -p ${BUILD_DIR}

COPY ./build_env/copy_to_org_repo.sh ${BUILD_DIR}/copy_to_org_repo.sh
COPY ./build_env/remove_update.sh ${BUILD_DIR}/remove_update.sh

RUN apt-get update \
	&& apt-get install -y subversion rsync git \
	&& apt-get clean -y \
	&& rm -rf /var/lib/apt/lists/*
#	&& curl -sS https://getcomposer.org/installer -o ./composer-setup.php \
#	&& HASH=`curl -sS https://composer.github.io/installer.sig` php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
#	&& php ./composer-setup.php --install-dir=./ --filename=composer.phar \
#	&& rm -f ./composer-setup.php

ENTRYPOINT ["/build_env/copy_to_org_repo.sh"]
