FicSave
=======

This is the repo for FicSave, an open-source online fanfiction downloader.

# THIS BRANCH IS NO LONGER MAINTAINED. CHECK OUT THE `ficsave-2.0` BRANCH TO FOLLOW ALONG WITH FURTHER DEVELOPMENT.

# Frameworks/Libraries Used

### Laravel 5

[The PHP Framework For Web Artisans.](https://laravel.com/)

### Materialize

[A modern responsive front-end framework based on Material Design.](http://materializecss.com/)

### PHPePub

[PHP Classes for dynamically generating EPub files.](https://github.com/Grandt/PHPePub)

### QueryPath

[QueryPath is an XML and HTML DOM manipulation PHP library.](http://querypath.org/)

### HTML5-PHP

[An HTML5 parser and serializer for PHP.](http://masterminds.github.io/html5-php/)

### Calibre (ebook-convert)

[Convert an ebook from one format to another.](http://manual.calibre-ebook.com/cli/ebook-convert.html)

# How To Setup Your Own FicSave Instance

1. Download all dependencies using `composer install`.
2. Install Calibre.
3. Add this to your crontab: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1` or execute `php /path/to/artisan schedule:run` every minute.
