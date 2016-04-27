<?php
    $_SESSION['currentId'] = uniqid();
?>
<!DOCTYPE html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>FicSave - An Open-Source Online Fanfiction Downloader</title>
        <meta name="description" content="An open-source online fanfiction downloader. Download fanfics as eBooks from various sources online, for free.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

        <link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="60x60" href="/apple-touch-icon-60x60.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png?v=rMMlz4K2XJ">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon-180x180.png?v=rMMlz4K2XJ">
        <link rel="icon" type="image/png" href="/favicon-32x32.png?v=rMMlz4K2XJ" sizes="32x32">
        <link rel="icon" type="image/png" href="/android-chrome-192x192.png?v=rMMlz4K2XJ" sizes="192x192">
        <link rel="icon" type="image/png" href="/favicon-96x96.png?v=rMMlz4K2XJ" sizes="96x96">
        <link rel="icon" type="image/png" href="/favicon-16x16.png?v=rMMlz4K2XJ" sizes="16x16">
        <link rel="manifest" href="/manifest.json?v=rMMlz4K2XJ">
        <link rel="shortcut icon" href="/favicon.ico?v=rMMlz4K2XJ">
        <meta name="apple-mobile-web-app-title" content="FicSave">
        <meta name="application-name" content="FicSave">
        <meta name="msapplication-TileColor" content="#64b5f6">
        <meta name="msapplication-TileImage" content="/mstile-144x144.png?v=rMMlz4K2XJ">
        <meta name="theme-color" content="#64b5f6">

        <link rel="stylesheet" href="/css/normalize.css">
        <link type="text/css" rel="stylesheet" href="/css/materialize.css"  media="screen,projection"/>
        <link type="text/css" rel="stylesheet" href="/css/main.css?v=2.2" media="screen,projection"/>
    </head>
    <body>
        <main class="valign-wrapper grey-text text-lighten-4">
            <div class="container valign">
                <div class="row center-align">
                    <div class="col s12">
                        <h1 id="header">FicSave</h1>
                        <h3 id="subheader" class="grey-text text-lighten-3">An Open-Source Online Fanfiction Downloader</h3>
                    </div>
                </div>
                <div class="row">
                    <form id="download" class="col s12" action="/api/downloader/begin" method="POST">
                        <div class="row">
                            <div class="input-field col s9 l5">
                                <input id="url" type="url" name="url" class="validate"<?= isset($_GET['url']) ? ' value="'.$_GET['url'].'"' : '' ?>>
                                <label for="url" class="grey-text text-lighten-1">Fanfic URL</label>
                            </div>
                            <div class="input-field col s3 l2">
                                <select id="format" name="format">
                                    <option value="epub"<?= isset($_GET['format']) ? ($_GET['format'] == 'epub' ? ' selected' : '') : ' selected' ?>>ePub</option>
                                    <option value="mobi"<?= isset($_GET['format']) ? ($_GET['format'] == 'mobi' ? ' selected' : '') : '' ?>>MOBI</option>
                                    <option value="pdf"<?= isset($_GET['format']) ? ($_GET['format'] == 'pdf' ? ' selected' : '') : '' ?>>PDF</option>
                                    <option value="txt"<?= isset($_GET['format']) ? ($_GET['format'] == 'txt' ? ' selected' : '') : '' ?>>Text</option>
                                </select>
                                <label class="grey-text text-lighten-1" for="format">Format</label>
                            </div>
                            <div class="input-field col s12 l3">
                                <input id="email" type="email" name="email" class="validate tooltipped" data-delay="0" data-position="bottom" data-tooltip="Use MOBI format for Kindle emails."<?= isset($_GET['em']) ? ' value="'.base64_decode($_GET['em']).'"' : '' ?>>
                                <label for="email" class="grey-text text-lighten-1">Email (optional)</label>
                            </div>
                            <div class="input-field col s12 l2">
                                <input id="currentId" type="hidden" name="currentId" value="<?= $_SESSION['currentId'] ?>">
                                <button class="btn waves-effect col s12" id="download-submit">Download</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row">
                    <div class="col s12 l7 offset-l2 center-align" style="max-height: 200px; overflow-y: auto;">
                        <table id="downloads" class="responsive-table centered"></table>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12 l4 offset-l4 center-align">
                        <a class="modal-trigger btn waves-effect" href="#bookmarklet">Create Bookmarklet</a>
                    </div>
                </div>
                <?php
                try {
                    include dirname(dirname(__FILE__)) . '/include/custom/donations.php';
                ?>
                <div class="row">
                    <div class="col s12 center-align" style="margin: 20px auto 5px auto;">
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                            <input type="hidden" name="cmd" value="_s-xclick">
                            <input type="hidden" name="hosted_button_id" value="NQNTQ5GHRKSPN">
                            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                            <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                        </form>
                        <div>Every donation helps keep the site running!</div>
                        <div>Donations this month (after fees):</div>
                        <div style='font-size:20px;'><strong>$<?= getDonateAmount() ?> USD</strong></div>
                    </div>
                </div>
                <?php
                } catch (Exception $ex) {

                }
                ?>
                <div class="row">
                    <div class="col s12 center-align">
                        <a href="#supported-sites" class="modal-trigger blue-text text-lighten-4">Supported Sites</a> &bull; <a href="#changelog" class="modal-trigger blue-text text-lighten-4">Changelog</a>
                    </div>
                </div>
            </div>
        </main>

        <div id="bookmarklet" class="modal modal-fixed-footer">
            <div class="modal-content">
                <h4>Bookmarklet</h4>
                <p>Create a bookmarklet to easily download fanfics! Clicking the bookmarklet on any fanfic page will open FicSave in another window and automatically start downloading the fanfic.</p>
                <form id="form-bookmarklet" class="col s12">
                    <div class="row">
                        <div class="col s12 l3 offset-l2">
                            <label class="grey-text text-lighten-1" for="bookmarklet-format">Default Format</label>
                            <select id="bookmarklet-format" class="">
                                <option value="epub" selected>ePub</option>
                                <option value="mobi">MOBI</option>
                                <option value="pdf">PDF</option>
                                <option value="txt">Text</option>
                            </select>
                        </div>
                        <div class="input-field col s12 l3">
                            <input id="bookmarklet-email" type="email" class="validate">
                            <label for="bookmarklet-email" class="grey-text text-lighten-1">Email (optional)</label>
                        </div>
                        <div class="input-field col s12 l2">
                            <button class="btn waves-effect col s12">Create Bookmarklet</button>
                        </div>
                    </div>
                </form>
                <div id="bookmarklet-link" class="center-align tooltipped" style="display: none;" data-delay="0" data-tooltip="Bookmark me!"></div>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-action modal-close waves-effect btn-flat">Close</a>
            </div>
        </div>

        <div id="supported-sites" class="modal">
            <div class="modal-content">
                <h4>Supported Sites</h4>
                <ul>
                    <li>fanfiction.net</li>
                    <li>fictionpress.com</li>
                    <li>adult-fanfiction.org</li>
                    <li>hpfanficarchive.com</li>
                    <li>asianfanfics.com</li>
                </ul>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-action modal-close waves-effect btn-flat">Close</a>
            </div>
        </div>

        <div id="changelog" class="modal modal-fixed-footer">
            <div class="modal-content">
                <h4>Changelog</h4>
                <ul>
                    <li>
                        <strong>August 13th, 2015</strong>
                        <ul>
                            <li>FicSave has been given a makeover! The website has been rebuilt from scratch.</li>
                            <li>Added support for hpfanficarchive.com and asianfanfics.com.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>October 12th, 2014</strong>
                        <ul>
                            <li>Added support for FictionPress.com and Adult-Fanfiction.org.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>September 27th, 2014</strong>
                        <ul>
                            <li>Added Text File (txt) option to formats.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>September 26th, 2014</strong>
                        <ul>
                            <li>Added bookmarklets to FicSave.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>September 10th, 2014</strong>
                        <ul>
                            <li>Changed PDF generation to use the TCPDF library.</li>
                            <li>MOBI files now have a Table of Contents.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>June 20th, 2014</strong>
                        <ul>
                            <li>Added Kindle support.</li>
                            <li>It's my birthday!</li>
                        </ul>
                    </li>
                    <li>
                        <strong>May 29th, 2014</strong>
                        <ul>
                            <li>Fixed a compatibility issue with mobile browsers causing corrupted files to be sent</li>
                        </ul>
                    </li>
                    <li>
                        <strong>May 22nd, 2014</strong>
                        <ul>
                            <li>Grabbing fanfics is now asynchronous, meaning timeouts are a thing of the past</li>
                            <li>PDFs are now generated with Calibre</li>
                        </ul>
                    </li>
                    <li>
                        <strong>April 3rd, 2014</strong>
                        <ul>
                            <li>Fixed a case where a chapter would be missing sections of text</li>
                            <li>Fixed some UTF-8 characters not being formatted properly</li>
                        </ul>
                    </li>
                    <li>
                        <strong>March 21st, 2014</strong>
                        <ul>
                            <li>Fixed a case where long chapters would be truncated in the database</li>
                            <li>Added error handling for empty chapters</li>
                        </ul>
                    </li>
                    <li>
                        <strong>February 6st, 2014</strong>
                        <ul>
                            <li>Open-sourced to <a href="https://github.com/waylaidwanderer/FicSave" target="_blank">GitHub repo</a></li>
                            <li>Finalized MOBI support</li>
                            <li>Site tweaks</li>
                        </ul>
                    </li>
                    <li>
                        <strong>February 1st, 2014</strong>
                        <ul>
                            <li>Added PDF support</li>
                            <li>Added beta MOBI support</li>
                        </ul>
                    </li>
                    <li>
                        <strong>January 31st, 2014</strong>
                        <ul>
                            <li>Initial version of FicSave</li>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <a href="#!" class="modal-action modal-close waves-effect btn-flat">Close</a>
            </div>
        </div>

        <footer class="page-footer">
            <div class="container">
                <div class="row">
                    <div class="col l6 s12">
                        <h5 class="white-text">About</h5>
                        <p class="grey-text text-lighten-4">FicSave is an open-source online fanfiction downloader that allows you to save stories from various fanfiction sites for offline reading. Problems, or something wrong with the generated file? <a href="https://github.com/waylaidwanderer/FicSave/issues" class="blue-text text-lighten-4">Create an issue</a> on GitHub. Feel free to check out the source code while you're there!</p>
                        <p class="grey-text text-lighten-4">
                            All downloaded stories are stored on the server for 15 minutes before deletion.
                        </p>
                        <p class="grey-text text-lighten-4">
                            If you are requesting the file via email, please whitelist <strong>delivery@ficsave.com</strong> otherwise you'll need to look in your Junk folder.
                            <br/>
                            For questions/inquiries and keeping up with the latest news, follow me on Twitter <a href="https://twitter.com/FicSave" class="blue-text text-lighten-4">@FicSave</a>.</p>
                    </div>
                    <div class="col l3 s12 offset-l3">
                        <h5 class="white-text">Connect</h5>
                        <ul>
                            <li><a class="white-text" href="https://github.com/waylaidwanderer/FicSave">GitHub</a></li>
                            <li><a class="white-text" href="https://twitter.com/FicSave">Twitter</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-copyright">
                <div class="container">
                    Copyright &copy; 2015 FicSave.com. All Rights Reserved.
                </div>
            </div>
        </footer>
        <script src="/js/vendor/modernizr-2.8.3.min.js"></script>
        <script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
        <script>window.jQuery || document.write('<script src="/js/vendor/jquery-2.1.1.min.js"><\/script>')</script>
        <script src="/js/plugins.js"></script>
        <script src="/js/bin/materialize.min.js"></script>
        <script src="/js/main.js?v=2"></script>
        <script>
            $.post('/api/downloader/begin', { resume: 1, currentId: '<?= $_SESSION['currentId'] ?>' })
                .done(function(data) {
                    if (data.success) {
                        buildTable(data);
                        processLoop();
                    } else {
                        Materialize.toast(data.message, 5000, 'rounded');
                    }
                });

            $('#download-submit').click(function() {
                if ($('#url').val().trim() == '') {
                    Materialize.toast("URL cannot be empty!", 5000, 'rounded');
                } else {
                    if ($(this).text() != 'Loading...') {
                        $(this).text('Loading...');
                        $('#download').submit();
                    }
                }
                return false;
            });

            $('#download').submit(function() {
                var $downloadButton = $('#download').find('button');
                $.post('/api/downloader/begin', $(this).serialize())
                    .done(function(data) {
                        $downloadButton.text('Download');
                        if (data.success) {
                            buildTable(data);
                            processLoop();
                        } else {
                            Materialize.toast(data.message, 5000, 'rounded');
                        }
                    })
                    .fail(function() {
                        Materialize.toast("A server error has occurred. Please try again later.", 5000, 'rounded');
                    });
                $('#url').val('');
                return false;
            });

            <?php
            if (isset($_GET['download']) && $_GET['download'] == 'yes') {
            ?>
            $('#download').submit();
            <?php
            }
            ?>

            function buildTable(data) {
                // build table
                var $downloadsTable = $("#downloads");
                var $tbody = $downloadsTable.find('tbody');
                if ($tbody.length == 0) {
                    $tbody = $('<tbody/>');
                }
                for (var downloadId in data.downloads) {
                    if (data.downloads.hasOwnProperty(downloadId)) {
                        if ($('#'+downloadId).length == 0) {
                            var download = data.downloads[downloadId];
                            var $row = $('<tr id="'+downloadId+'"/>');
                            var $title = $('<td>'+download.story.title+' - '+download.story.author+'.'+download.format+'</td>');
                            var $progress = $('<td><span class="status">Pending...</span><span class="downloading" style="display: none;">Downloading chapter <span class="currentChapter">0</span> of <span class="totalChapters">'+download.totalChapters+'</span></span></td>');
                            $row.append($title).append($progress);
                            $tbody.prepend($row);
                        }
                    }
                }
                $downloadsTable.html($tbody);
            }

            function processLoop() {
                $.post('/api/downloader/process', { currentId: '<?= $_SESSION['currentId'] ?>' })
                    .done(function(data) {
                        if (data.success) {
                            // update table
                            var numIncomplete = 0;
                            var downloadIds = [];
                            for (var downloadId in data.downloads) {
                                if (data.downloads.hasOwnProperty(downloadId)) {
                                    downloadIds.push(downloadId);
                                    var download = data.downloads[downloadId];
                                    var $tableRow = $('#'+downloadId);
                                    var $statusSpan = $tableRow.find('.status');
                                    var $downloadingSpan = $tableRow.find('.downloading');
                                    switch (download.status) {
                                        case <?= Status::DOWNLOADING ?>:
                                            $downloadingSpan.find('.currentChapter').text(download.currentChapter);
                                            if ($downloadingSpan.css('display') == 'none') {
                                                $statusSpan.hide();
                                                $downloadingSpan.show();
                                            }
                                            break;
                                        case <?= Status::DOWNLOAD_COMPLETE ?>:
                                            $downloadingSpan.hide();
                                            $statusSpan.text('Finished downloading chapters.');
                                            $statusSpan.show();
                                            break;
                                        case <?= Status::BUILDING ?>:
                                            $downloadingSpan.hide();
                                            $statusSpan.text('Building eBook, please wait...');
                                            $statusSpan.show();
                                            break;
                                        case <?= Status::ERROR ?>:
                                            $downloadingSpan.hide();
                                            $statusSpan.text('Error: ' + download.statusMessage);
                                            $statusSpan.show();
                                            break;
                                        case <?= Status::DONE ?>:
                                        case <?= Status::SERVED ?>:
                                            $downloadingSpan.hide();
                                            var url = '/download/' + downloadId;
                                            $statusSpan.html('<a href="'+url+'" class="blue-text text-lighten-3">Download File</a>');
                                            $statusSpan.show();
                                            if (download.status == <?= Status::DONE ?>) {
                                                window.location.href = url;
                                            }
                                            break;
                                        case <?= Status::EMAILED ?>:
                                            $downloadingSpan.hide();
                                            $statusSpan.text('Email sent!');
                                            $statusSpan.show();
                                            break;
                                    }
                                    if (download.status != <?= Status::ERROR ?> && download.status <= <?= Status::DONE ?>) {
                                        numIncomplete++;
                                    }
                                }
                            }
                            $('#downloads').find('tr').each(function() {
                                if (downloadIds.indexOf(this.id) == -1) {
                                    $(this).remove();
                                }
                            });

                            if (numIncomplete > 0) {
                                processLoop();
                            }
                        } else {
                            $('#downloads').hide();
                            Materialize.toast(data.message, 5000, 'rounded');
                        }
                    })
                    .fail(function() {
                        window.location.href = '/';
                    });
            }
        </script>
        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-38190232-3', 'ficsave.com');
            ga('send', 'pageview');
        </script>
    </body>
</html>
