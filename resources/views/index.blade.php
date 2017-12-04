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
    <input id="session-id" type="hidden" value="{{ Session::getId() }}"/>
    <main class="valign-wrapper grey-text text-lighten-4">
        <div class="container valign">
            <div class="row center-align">
                <div class="col s12">
                    <h1 id="header">FicSave</h1>
                    <h3 id="subheader" class="grey-text text-lighten-3">An Open-Source Online Fanfiction Downloader</h3>
                </div>
            </div>
            <div class="row">
                <form id="download" class="col s12" action="{{ URL::route('download-begin') }}" method="POST">
                    <div class="row">
                        <div class="input-field col s9 l5">
                            <input id="url" type="url" name="url" class="validate" value="{{ Request::input('url', '') }}"/>
                            <label for="url" class="grey-text text-lighten-1">Fanfic URL</label>
                        </div>
                        <div class="input-field col s3 l2">
                            <select id="format" name="format">
                                <option value="epub"{{ is_null(Request::input('format')) ? '' : (Request::input('format') == 'epub' ? ' selected' : '') }}>ePub</option>
                                <option value="mobi"{{ is_null(Request::input('format')) ? '' : (Request::input('format') == 'mobi' ? ' selected' : '') }}>MOBI</option>
                                <option value="txt"{{ is_null(Request::input('format')) ? '' : (Request::input('format') == 'txt' ? ' selected' : '') }}>Text</option>
                                {{-- <option value="pdf"{{ is_null(Request::input('format')) ? '' : (Request::input('format') == 'pdf' ? ' selected' : '') }}>PDF</option> --}}
                            </select>
                            <label class="grey-text text-lighten-1" for="format">Format</label>
                        </div>
                        <div class="input-field col s12 l3">
                            <input id="email" type="email" name="email" class="validate tooltipped" data-delay="0" data-position="bottom" data-tooltip="Use MOBI format for Kindle emails."{!! is_null(Request::input('em')) ? '' : ' value="'.base64_decode(Request::input('em')).'"' !!}/>
                            <label for="email" class="grey-text text-lighten-1">Email (optional)</label>
                        </div>
                        <div class="input-field col s12 l2">
                            <input id="currentId" type="hidden" name="currentId" value="{{ Request::session()->get('currentId') }}">
                            <button class="btn waves-effect col s12" id="download-submit">Download</button>
                        </div>
                    </div>
                    {{ csrf_field() }}
                </form>
            </div>
            <div class="row">
                <div class="col s12 l7 offset-l2 center-align" style="max-height: 200px; overflow-y: auto;">
                    <table id="downloads" class="responsive-table centered">
                        <tbody v-for="download in downloads | orderBy 'timestamp' -1">
                            <tr>
                                <td v-text="download.story.title + ' - ' + download.story.author + '.' + download.format"></td>
                                <td>
                                    <span v-if="download.status == -1" v-text="'Error: ' + download.statusMessage"></span>
                                    <span v-if="download.status == 0" v-text="'Pending'"></span>
                                    <span v-if="download.status == 1" v-text="'Downloading chapter ' + download.currentChapter + ' of ' + download.numChapters"></span>
                                    <span v-if="download.status == 2" v-text="'Download complete; starting build...'"></span>
                                    <span v-if="download.status == 3" v-text="'Building eBook...'"></span>
                                    <span v-if="download.status == 4 || download.status == 5"><a :href="'/download/' + download.id" class="blue-text text-lighten-3" v-text="'Download File'"></a></span>
                                    <span v-if="download.status == 6" v-text="'Email sent!'"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!--
            <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
            <!-- Homepage
            <ins class="adsbygoogle"
                 style="display:block"
                 data-ad-client="ca-pub-1951734480443800"
                 data-ad-slot="4494563975"
                 data-ad-format="auto"></ins>
            <script>
                (adsbygoogle = window.adsbygoogle || []).push({});
            </script>
            -->
            <div class="row">
                <div class="col s12 l4 offset-l4 center-align">
                    <a class="modal-trigger btn waves-effect" href="#bookmarklet">Create Bookmarklet</a>
                </div>
            </div>
            @include('custom.donations')
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
                            {{-- <option value="pdf">PDF</option> --}}
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
            <a href="#" class="modal-action modal-close waves-effect btn-flat">Close</a>
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
                <li>asianfanfics.com (currently having issues)</li>
                <li>portkey-archive.org</li>
            </ul>
        </div>
        <div class="modal-footer">
            <a href="#" class="modal-action modal-close waves-effect btn-flat">Close</a>
        </div>
    </div>

    <div id="changelog" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4>Changelog</h4>
            <ul>
                <li>
                    <strong>June 16th, 2016</strong>
                    <ul>
                        <li>FicSave's backend has been updated to use Laravel 5 instead of Slim Framework.</li>
                        <li>URL has been changed from http://ficsave.com to http://ficsave.xyz.</li>
                        <li>Parsing of adult-fanfiction.org has been fixed.</li>
                    </ul>
                </li>
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
            <a href="#" class="modal-action modal-close waves-effect btn-flat">Close</a>
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
                        If you are requesting the file via email, please whitelist <strong>delivery@ficsave.xyz</strong> otherwise you'll need to look in your Junk folder. If you're using Amazon's Send to Kindle feature, whitelist your own Kindle email instead!
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
                Copyright &copy; 2015 FicSave. All Rights Reserved.
            </div>
        </div>
    </footer>
    <script src="/js/vendor/modernizr-2.8.3.min.js"></script>
    <script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
    <script>window.jQuery || document.write('<script src="/js/vendor/jquery-2.1.1.min.js"><\/script>')</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/1.0.28/vue.js"></script>
    <script src="/js/plugins.js"></script>
    <script src="/js/bin/materialize.min.js"></script>
    <script src="/js/bin/websocketex.js"></script>
    <script>
        var socketAddress = '{{ env('SOCKET_ADDRESS') }}';
        var startDownload = {{ Request::input('download', 'no') == 'yes' ? 'true' : 'false' }};
        var downloadUrl = '{{ URL::route('download-begin') }}';
    </script>
    <script src="/js/main.js?v=2.0.1"></script>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-38190232-3', 'auto');
        ga('send', 'pageview');
    </script>
</body>
</html>
