#!/bin/bash

$(dirname "$0")/vendor/bin/satis add -vvv --no-ansi --no-interaction --type=git ${1} $(dirname "$0")/satis.json
