FicSave
=======

This is the repo for FicSave, an open-source online fanfiction downloader.

# Frameworks/Libraries Used

### Foundation

[The most advanced responsive front-end framework in the world.](http://foundation.zurb.com/)

### PHPePub

[PHP Classes for dynamically generating EPub files.](https://github.com/Grandt/PHPePub)

### phpMobi

[An experimental Mobipocket file creator in PHP](https://github.com/raiju/phpMobi)

### Calibre

[An open source e-book library manager for Linux, Macintosh and Windows platforms.](http://calibre-ebook.com/)

# How To Setup Your Own FicSave

FicSave should run pretty well out of the box on any webserver with PHP, but you will need to run `cron.php` using a cronjob or similar service every once in a while, to remove old files from `/tmp/`.
