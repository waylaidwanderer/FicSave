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

FicSave runs on a Linux system, and as such, the function for Calibre ebook conversion works on Linux only (but should be trivial to convert to Windows).

First, you will need to install [Calibre for linux](http://calibre-ebook.com/download_linux), and then run the following command to allow `ebook-convert` to work on headless servers:

    sudo apt-get install xvfb