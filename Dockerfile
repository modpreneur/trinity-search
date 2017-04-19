FROM modpreneur/trinity-test

MAINTAINER Barbora Čápová <capova@modpreneur.com>

# Install app
ADD . /var/app

WORKDIR /var/app


RUN chmod +x entrypoint.sh

ENTRYPOINT ["sh", "entrypoint.sh", "service postfix start"]