# satis-ci
[![GitHub license](https://img.shields.io/github/license/kberzinch/satis-ci.svg?style=flat-square)](https://raw.githubusercontent.com/kberzinch/deploy/master/LICENSE.md)

Plumbing to publish Composer packages from GitHub to Satis, with as little fuss as possible.

## Initial server setup
1. Clone this repository to your server and set up a PHP web server.
2. Run `composer install` to install dependencies.
3. Validate by visiting `/github` - you should get a signature verification failure.

## GitHub App setup
Once your server is set up, visit `/setup` and follow the prompts.

## Finishing up
If you'd like to get emails, set `$email_from` and `$email_to`. This will trigger an email any time a build fails.

Configure satis as usual. Your `satis.json` file should live in the root of this project.
