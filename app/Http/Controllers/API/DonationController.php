<?php

namespace App\Http\Controllers\API;

use App\PaypalDonation;
use App\PaypalIpn\IpnListener;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;

class DonationController extends Controller
{
    public function postPaypal(Request $request)
    {

    }
}
