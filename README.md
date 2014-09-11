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

### TCPDF

[TCPDF is a FLOSS PHP class for generating PDF documents.](http://www.tcpdf.org/)

# How To Setup Your Own FicSave

FicSave should run pretty well out of the box on any webserver with PHP, but you will need to run `cron.php` using a cronjob or similar service every once in a while, to remove old files from `/tmp/`.
