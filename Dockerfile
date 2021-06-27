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

ENTRYPOINT ["/build_env/copy_to_org_repo.sh"]
