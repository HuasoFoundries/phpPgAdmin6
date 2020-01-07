# phpPgAdmin6

PHP Based administration tool for PostgreSQL. Blazing fast routing with [Slim Framework 3](https://www.slimframework.com/) and solid abstraction layer in its core with [AdoDB](https://adodb.org/). Originally forked from [phppgadmin/phppgadmin](https://github.com/phppgadmin/phppgadmin).

[![Packagist](https://img.shields.io/packagist/dm/huasofoundries/phppgadmin6.svg)](https://packagist.org/packages/huasofoundries/phppgadmin6)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/289a56c1c7d94216b3d089c220689e9e)](https://www.codacy.com/app/amenadiel/phpPgAdmin6?utm_source=github.com&utm_medium=referral&utm_content=HuasoFoundries/phpPgAdmin6&utm_campaign=Badge_Grade)
[![StyleCI](https://styleci.io/repos/21398998/shield?branch=develop)](https://styleci.io/repos/21398998)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/?branch=develop)
[![Build Status](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/build-status/develop)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FHuasoFoundries%2FphpPgAdmin6.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2FHuasoFoundries%2FphpPgAdmin6?ref=badge_shield)

This is a hard fork of [phppgadmin](https://github.com/phppgadmin/phppgadmin) which adds the following enhancements:

- Composer Installation and dependency management
- Autoloading (thanks to the above)
- Namespaced classes
- Removal of global variables
- Full PHP 7+ support
- Support for PG 9.3+ features (Materialized Views, BRIN Indexes, etc)
- Nice urls

### WIP

Other enhancements are in progress and would be a nice to have:

- Replace usage of superglobals with [PSR-7 Message interfaces](http://www.php-fig.org/psr/psr-7/) to carry information around.
- Usage of Dependency Injection compliant with [PSR-11 Container interface](http://www.php-fig.org/psr/psr-11/)

This project is made on top of [Slim Framework 3](https://www.slimframework.com/) and communicates with the Database using [AdoDB](https://adodb.org/)

## Installation

### Using Composer (recommended)

[Install Composer in your machine](https://getcomposer.org/download/).

Install with composer running the following command in your shell (replacing <FOLDER> whith your desired folder name)

```sh
composer create-project huasofoundries/phppgadmin6 <FOLDER> v6.0.*@rc --no-dev --prefer-dist
```

Alternatively, clone this repo and run (inside then folder where the project was cloned)

```sh
composer install --no-dev
```

#### Installing dev branch

If there's something broken and I cannot risk breaking the rest to fix your issue, I might push a fix or feature to [develop branch](https://github.com/HuasoFoundries/phpPgAdmin6/tree/develop). Said branch can be installed as

```sh
composer create-project huasofoundries/phppgadmin6 <FOLDER> *@beta --no-dev --prefer-dist
```

(or, you know, clone the repo and make sure you're in develop branch)

## Rewrite Rules

As this project is built over [Slim Framework 3](https://www.slimframework.com/), **you'll need some rewrite rules for nice-urls to work**.

### Apache

Make sure you have the RewriteEngine module active in your Apache installation.

Place an `.htaccess` file on your project root with the following contents

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

```

### Nginx

Add the following vhost to your `sites-enabled` folder

```
server {
        listen 80;
        # or whatever port you want

        server_name yourservername.com;

        root /path/to/project;

        index index.php;

        # Use this block if you're running in your domain or subdomain root
        location / {
          try_files $uri $uri/ /index.php$is_args$args;
        }

      # If running inside a subfolder use instead
        #location /subfolder/ {
        #   try_files $uri $uri/ /subfolder/index.php$is_args$args;
        #}

        # pass the PHP scripts to FastCGI server listening on IP:PORT or socket
        location ~ \.php$ {
                fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
                fastcgi_split_path_info ^(.+\.php)(/.+)$;

                # Check that the PHP script exists before passing it
                try_files $fastcgi_script_name =404;

                # Bypass the fact that try_files resets $fastcgi_path_info
                # see: http://trac.nginx.org/nginx/ticket/321
                set $path_info $fastcgi_path_info;
                fastcgi_param PATH_INFO $path_info;

                fastcgi_index index.php;
                include /etc/nginx/fastcgi_params;
                fastcgi_pass unix:/run/php/php7.0-fpm.sock;
                # or fastcgi_pass 127.0.0.1:9000; depending on your PHP-FPM pool
        }
}
```

Please note that you have to customize your server name, php upstream (sock or IP) and optinally the subfolder you want phpPgAdmin6 to run on.

## License

This work is licensed under MIT or GPL 2.0 (or any later version) or BSD-3-Clause
You can choose between one of them if you use this work.

`SPDX-License-Identifier: MIT OR GPL-2.0-or-later OR BSD-3-Clause`

## Credits & FAQ

We're preserving due credits to all people that contributed in the past, as well as other release notes
contained in the old version of [phppgadmin](https://github.com/phppgadmin/phppgadmin)

- [Bugs](docs/BUGS.md)
- [Changelog](docs/CHANGELOG.md) (_outdated_)
- [Credits](docs/CREDITS.md)
- [Developers](docs/DEVELOPERS.md)
- [FAQ](docs/FAQ.md) (_outdated_)
- [History](docs/HISTORY.md) (_outdated_)
- [Translators](docs/TRANSLATORS.md)

Kudos to all people that helped build the original project, upon which this one was built.
