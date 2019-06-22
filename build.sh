#!/bin/bash

$(dirname "$0")/vendor/bin/satis build -vvv --no-ansi --no-interaction --repository-url ${1} $(dirname "$0")/satis.json $(dirname "$0")/build
