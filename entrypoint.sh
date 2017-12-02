#!/bin/sh sh

composer update

phpunit

phpstan analyse Controller/ DataFixtures/ DependencyInjection/ Exception/ NQL/ Serialization/ Utils/ --level=4

tail -f /dev/null