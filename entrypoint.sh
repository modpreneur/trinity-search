#!/bin/bash sh

composer update

./vendor/phpunit/phpunit/phpunit

while true; do sleep 1000; done
