FROM ubuntu:22.04
ENV DEBIAN_FRONTEND noninteractive
ENV PHP_VERSION 8.1

RUN	\
	apt-get update && \
	apt -y install curl php${PHP_VERSION} php${PHP_VERSION}-cli php${PHP_VERSION}-curl

COPY src /usr/local/bin/src
ENTRYPOINT /usr/bin/php /usr/local/bin/src/run.php
