<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-06-15
 * Time: 6:23 PM
 */

namespace App\Enums;


class DownloadStatus
{
    const PENDING = 0;
    const DOWNLOADING = 1;
    const DOWNLOAD_COMPLETE = 2;
    const BUILDING = 3;
    const DONE = 4;
    const SERVED = 5;
    const EMAILED = 6;
    const ERROR = -1;
}
