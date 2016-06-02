FROM modpreneur/trinity-test:0.1.1

MAINTAINER Martin Kolek <kolek@modpreneur.com>

# Install app
ADD . /var/app

WORKDIR /var/app



RUN chmod +x entrypoint.sh

ENTRYPOINT ["sh", "entrypoint.sh", "service postfix start"]