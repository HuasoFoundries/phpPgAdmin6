# phpPgAdmin6

[![Packagist](https://img.shields.io/packagist/dm/huasofoundries/phppgadmin6.svg)](https://packagist.org/packages/huasofoundries/phppgadmin6)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/289a56c1c7d94216b3d089c220689e9e)](https://www.codacy.com/app/amenadiel/phpPgAdmin6?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=HuasoFoundries/phpPgAdmin6&amp;utm_campaign=Badge_Grade) [![StyleCI](https://styleci.io/repos/21398998/shield?branch=develop)](https://styleci.io/repos/21398998) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/?branch=develop) [![Build Status](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/HuasoFoundries/phpPgAdmin6/build-status/develop)

PHP Based administration tool for PostgreSQL. 

This is a hard fork of [phppgadmin](https://github.com/phppgadmin/phppgadmin) which aims to add the following enhancements:

- Composer Installation and dependency management
- Autoloading (thanks to the above)
- Namespaced classes
- Removal of global variables
- Full PHP 7+ support
- Support for PG 9.3+ features (Materialized Views, BRIN Indexes, etc)
- Nice urls
- Replace usage of superglobals with [PSR-7 Message interfaces](http://www.php-fig.org/psr/psr-7/) to carry information around.
- Usage of Dependency Injection compliant with [PSR-11 Container interface](http://www.php-fig.org/psr/psr-11/)

Some of these are already in place, others are in progress.

This project is made on top of [Slim Framework 3](https://www.slimframework.com/), although a big part of the code doesn't use its full features yet.


## Installation

### Using Composer (recommended)

[Install Composer in your machine](https://getcomposer.org/download/).

Install with composer running the following command in your shell (replacing <FOLDER> whith your desired folder name)


```sh
composer create-project huasofoundries/phppgadmin6 <FOLDER> *@beta
```

Alternatively, clone this repo and run (inside then folder where the project was cloned)

```sh
composer install --no-dev
```


## Rewrite Rules

As this project is built over Slim PHP v3, you'll need some rewrite rules for this software to work. 

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

        server_name yourservername.com

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






