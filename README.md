# phpPgAdmin6

PHP Based administration tool for PostgreSQL. Blazing fast routing with [Slim Framework 3](https://www.slimframework.com/) and solid abstraction layer in its core with [AdoDB](https://adodb.org/). Originally forked from [phppgadmin/phppgadmin](https://github.com/phppgadmin/phppgadmin).

[![Packagist](https://img.shields.io/packagist/dm/huasofoundries/phppgadmin6.svg)](https://packagist.org/packages/huasofoundries/phppgadmin6)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/289a56c1c7d94216b3d089c220689e9e)](https://www.codacy.com/app/amenadiel/phpPgAdmin6?utm_source=github.com&utm_medium=referral&utm_content=HuasoFoundries/phpPgAdmin6&utm_campaign=Badge_Grade)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/?branch=develop)
[![Build Status](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/build-status/develop)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FHuasoFoundries%2FphpPgAdmin6.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2FHuasoFoundries%2FphpPgAdmin6?ref=badge_shield)

This is a hard fork of [phppgadmin](https://github.com/phppgadmin/phppgadmin) which adds the following enhancements:

-   Composer Installation and dependency management
-   [PSR-2 Coding Standard](https://www.php-fig.org/psr/psr-2) (Will evolve to PSR-12 soon)
-   [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4)
-   Removal of global variables (WIP)
-   Removal of superglobals in favour of [PSR-7 Message interfaces](http://www.php-fig.org/psr/psr-7/) (WIP)
-   Full PHP 7+ support
-   Usage of Dependency Injection compliant with [PSR-11 Container interface](http://www.php-fig.org/psr/psr-11/)
-   Support for PG 9.3+ features (Materialized Views, BRIN Indexes, etc)
-   Nice urls

### WIP

Other enhancements are in progress and would be a nice to have:

## Requirements

-   PHP 7.1+
-   ext-psql
-   [Composer](https://getcomposer.org/download/)

(If you're using PHP 5.6+, you can still try versions RC2 and below, but you should really, realy upgrade).

---

## Installation

### Using Composer (recommended)

[Install Composer in your machine](https://getcomposer.org/download/).

Install with composer running the following command in your shell (replacing <FOLDER> whith your desired folder name)

```sh
composer create-project huasofoundries/phppgadmin6 <FOLDER> v6.0.* --no-dev --prefer-dist
```

Alternatively, clone this repo and run (inside then folder where the project was cloned)

```sh
composer install --no-dev
```

## Configuration

You can set the config options either in a `config.inc.php` (refer to [config.inc.php-dist](config.inc.php-dist) for an example)
AND/OR a [config.yml](config.yml). The use of the latter is complely optional. Keep in mind the config entries are merged giving
precedence to the ones in the YAML file.

### Server Blocks

Configuration has a `servers` entry whose details are in their on Wiki section: "[Config: Servers](https://github.com/HuasoFoundries/phpPgAdmin6/wiki/Config:-servers)"

---

## Rewrite Rules

As this project is built over [Slim Framework 3](https://www.slimframework.com/), **you'll need some rewrite rules for nice-urls to work**.

Please refer to Slim Framework 3 instructions on rewrite rules config for:

-   [Nginx](http://www.slimframework.com/docs/v3/start/web-servers.html#nginx-configuration)
-   [Apache](http://www.slimframework.com/docs/v3/start/web-servers.html#apache-configuration)
-   [Lighttpd](http://www.slimframework.com/docs/v3/start/web-servers.html#lighttpd)
-   [IIS](http://www.slimframework.com/docs/v3/start/web-servers.html#iis)
-   [HHVM](http://www.slimframework.com/docs/v3/start/web-servers.html#hiphop-virtual-machine)
-   [PHP Built-in dev server](http://www.slimframework.com/docs/v3/start/web-servers.html#php-built-in-server)

## Running inside a subfolder

If you're planning to run phpPgAdmin6 under a subfolder, make sure you set it **explicitly** in the config file(s). I gave up trying to
figure out the subfolder automatically and it's outside of this project's scope.

To set it in `config.inc.php`

```
$conf = [
  'subfolder' => '/phppga_subfolder',
  'other config...' => 'sure'
];
```

To set it in `config.yml`

```yaml
default_lang: auto
subfolder: '/phppha_subfolder'
```

Remember that values set on the `yml` config take precedence.

Besides, remember to modify your webserver configuration accordingly

```
location /subfolder/ {
    try_files $uri $uri/ /subfolder/index.php$is_args$args;
}
```

Instead of

```
location / {
    try_files $uri $uri/ /index.php$is_args$args;
}
```

(Implementation details for your specific setup fall **outside of this package's scope**)

#### Installing dev branch

If there's something broken and I cannot risk breaking the rest to fix your issue, I might push a fix or feature to [develop branch](https://github.com/HuasoFoundries/phpPgAdmin6/tree/develop). Said branch can be installed as

```sh
composer create-project huasofoundries/phppgadmin6 <FOLDER> v6.*.*@beta --no-dev --prefer-dist
```

(or, you know, clone the repo and make sure you're in develop branch)

## License

This work is licensed under MIT or GPL 2.0 (or any later version) or BSD-3-Clause
You can choose between one of them if you use this work.

`SPDX-License-Identifier: MIT OR GPL-2.0-or-later OR BSD-3-Clause`

## Credits & FAQ

We're preserving due credits to all people that contributed in the past, as well as other release notes
contained in the old version of [phppgadmin](https://github.com/phppgadmin/phppgadmin)

-   [Bugs](docs/BUGS.md)
-   [Changelog](docs/CHANGELOG.md) (_outdated_)
-   [Credits](docs/CREDITS.md)
-   [Developers](docs/DEVELOPERS.md)
-   [FAQ](docs/FAQ.md) (_outdated_)
-   [History](docs/HISTORY.md) (_outdated_)
-   [Translators](docs/TRANSLATORS.md)

Kudos to all people that helped build the original project, upon which this one was built.
