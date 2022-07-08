<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ZohoController extends Controller
{
    public function auth(Request $request)
    {
        $uri = route('zohocrm');
        $scope =  'ZohoInvoice.contacts.Create';
        $clientid = 'here your zoho clientid';
        $accestype = 'offline';

        $redirectTo = 'https://accounts.zoho.com/oauth/v2/auth' . '?' . http_build_query(
                [
                    'client_id' => $clientid,
                    'redirect_uri' => $uri,
                    'scope' => 'ZohoInvoice.contacts.Create',
                    'response_type' => 'code',
                ]);

        \Session()->put('zoho_contact_id', $request->id);

        return redirect($redirectTo);
    }
    public function store(Request $request)
    {
        $input = $request->all();
        $contact_id = \Session::get('zoho_contact_id');
        $client_id = 'Here your zoho crm client_id';
        $client_secret = 'Here your zoho crm client_secret';
        \Session::forget('zoho_contact_id');

        // Get ZohoCRM Token
        $tokenUrl = 'https://accounts.zoho.com/oauth/v2/token?code='.$input["code"].'&client_id='.$client_id.'&client_secret='.$client_secret.'&redirect_uri='.route('zohocrm').'&grant_type=authorization_code';

        $tokenData = [

        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 300);
        curl_setopt($curl, CURLOPT_POST, TRUE);//Regular post
        curl_setopt($curl, CURLOPT_URL, $tokenUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($tokenData));

        $tResult = curl_exec($curl);
        curl_close($curl);
        $tokenResult = json_decode($tResult);
        // dd($tokenResult->);
        if(isset($tokenResult->access_token) && $tokenResult->access_token != '') {
            $getContact = Contact::where('id', $contact_id)->first();

            // Add Contact in ZohoCRM
            $jsonData = '{
                "contact_name": "'.$getContact->company_name.'",
                "company_name": "'.$getContact->company_name.'",
                "website": "'.$getContact->website_url.'",
                "billing_address": {
                    "attention": "Mr.'.$getContact->userName.'",
                    "address": "'.$getContact->getApplication.'",
                    "street2": "",
                    "state_code": "",
                    "city": "'.$getContact->city.'",
                    "state": "'.$getContact->state_country.'",
                    "zip": '.$getContact->zip_code.',
                    "country": "'.$getContact->country.'",
                    "fax": "",
                    "phone": "'.$getContact->phone_no.'"
                },
                "shipping_address": {
                    "attention": "Mr.'.$getContact->userName.'",
                    "address": "'.$getContact->getApplication.'",
                    "street2": "",
                    "state_code": "",
                    "city": "'.$getContact->city.'",
                    "state": "'.$getContact->state_country.'",
                    "zip": '.$getContact->zip_code.',
                    "country": "'.$getContact->country.'",
                    "fax": "",
                    "phone": "'.$getContact->phone_no.'"
                },
                "contact_persons": [
                    {
                        "salutation": "Mr",
                        "first_name": "'.$getContact->userName.'",
                        "last_name": "",
                        "email": "'.$getContact->userEmail.'",
                        "phone": "'.$getContact->phone_no.'",
                        "mobile": "'.$getContact->userMobile.'",
                        "is_primary_contact": true
                    }
                ]
            }';

            $curl = curl_init('https://invoice.zoho.com/api/v3/contacts');
            curl_setopt($curl, CURLOPT_VERBOSE, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_TIMEOUT, 300);
            curl_setopt($curl, CURLOPT_POST, TRUE);//Regular post
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Authorization: Zoho-oauthtoken ".$tokenResult->access_token,
                "X-com-zoho-invoice-organizationid: 688931512"
            ) );
            curl_setopt($curl, CURLOPT_POSTFIELDS,'JSONString='.$jsonData);

            //Execute cUrl session
            $cResponse = curl_exec($curl);
            curl_close($curl);

            $contactResponse = json_decode($cResponse);
            // echo "<pre>";
            // print_r($jsonData);
            // dd($contactResponse);
            if(isset($contactResponse->code) && $contactResponse->code == 0) {
                \Session::put('success','Contact created in ZohoCRM successfully.!');
                return redirect()->route('contacts');
            } else {
                \Session::put('error','Contact not create, please try again.!!');
                return redirect()->route('contacts');
            }
        } else {
            \Session::put('error','ZohoCRM token not generated, please try again.!!');
            return redirect()->route('contacts');
        }
    }

}
