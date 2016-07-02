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
        try {
            $listener = new IpnListener($request->input());
            $verified = $listener->processIpn();
            if ($verified) {
                Log::debug(var_export($request->input(), true));
                if ($request->input('payment_status') != 'Completed') exit(0);
                $txnId = $request->input('txn_id');
                if (!PaypalDonation::whereTxnId($request->input('txn_id'))->exists()) {
                    $donation = new PaypalDonation;
                    $donation->txn_id = $txnId;
                    $donation->payer_email = $request->input('payer_email');
                    $donation->mc_gross = $request->input('mc_gross');
                    $donation->mc_net = $request->input('mc_fee');
                    $donation->save();
                }
            } else {
                Log::error("Transaction not verified:\r\n" . var_export($request->input(), true));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
