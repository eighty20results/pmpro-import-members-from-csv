FROM debian:stable-slim

ENV BUILD_DIR /build_env
ENV BASE_DIR /

RUN mkdir -p ${BUILD_DIR}

COPY ./build_env/entrypoint.sh ${BUILD_DIR}/entrypoint.sh
COPY ./build_env/remove_update.sh ${BUILD_DIR}/remove_update.sh

RUN apt-get update \
	&& apt-get install -y subversion rsync git wget php-cli php-zip unzip curl \
	&& apt-get clean -y \
	&& rm -rf /var/lib/apt/lists/* \
	&& cd ~ \
	&& curl -sS https://getcomposer.org/installer -o composer-setup.php \
	&& HASH=$(curl -sS https://composer.github.io/installer.sig) \
	php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
	&& sudo php composer-setup.php --install-dir=./ --filename=composer.phar



ENTRYPOINT ["/build_env/entrypoint.sh"]
