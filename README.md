FicSave
=======

This is the repo for FicSave, an open-source online fanfiction downloader.

# Frameworks/Libraries Used

### Materialize

[A modern responsive front-end framework based on Material Design.](http://materializecss.com/)

### Slim

[Slim is a PHP micro framework that helps you quickly write simple yet powerful web applications and APIs.](http://www.slimframework.com/)

### PHPePub

[PHP Classes for dynamically generating EPub files.](https://github.com/Grandt/PHPePub)

### QueryPath

[QueryPath is an XML and HTML DOM manipulation PHP library.](http://querypath.org/)

### HTML5-PHP

[An HTML5 parser and serializer for PHP.](http://masterminds.github.io/html5-php/)

### Slim-Logger

[A stand-alone logger class for use with the Slim Framework.](https://github.com/codeguy/Slim-Logger)

### Calibre (ebook-convert)

[Convert an ebook from one format to another.](http://manual.calibre-ebook.com/cli/ebook-convert.html)

# How To Setup Your Own FicSave Instance

1. Download all dependencies using `composer update`.
2. Set up URL rewriting for Slim (See: http://docs.slimframework.com/routing/rewrite/)
3. Run `cleanup.sh` (Linux only; you'll have to write your own `.bat` equivalent if you are using Windows) and leave it open.