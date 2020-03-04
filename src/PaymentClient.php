<?php


namespace lms\PaymentClient;

use GuzzleHttp\Client;
use Rakit\Validation\Validator;

class PaymentClient
{

    private $base_url;
    private $app_key;

    public function _construct($base_url="payment\api",$key="uwtqeijugsajdgw564e6e5tfhsaluwqiwqha"){
        $this->base_url = $base_url;
        $this->app_key = $key;
    }

    public function doRequest(array $data=[]){
        try{

            $validator = new Validator;

            $validation = $validator->validate($data, [
                'user_id'                  => 'required',
                'order_id'                 => 'required',
                'payment_breakup' => 'required|array',
                'payment_breakup.*.amount' => 'required|numeric',
                'total_amount' => 'required|numeric'
            ]);

            if ($validation->fails()) {
                $errors = $validation->errors();
                return ['status'=>false,'message'=>"Invalid Payload"];
            } else {

                $payment_breakup = $data['payment_breakup'];

                $total_amount_pay = 0;

                foreach ($payment_breakup as $payment_data){
                    $payment_by = $payment_data['type'];
                    $amount = $payment_data['amount'];
                    $total_amount_pay = $total_amount_pay + $amount;
                }

                if($total_amount_pay != $data['total_amount']){
                    return ['status'=>false,'message'=>"Total amount is not sum up with payment breakup"];
                 }

                $client = new Client();
                $url = $this->base_url.'\payment';

                $head = [
                    'app_key' => $this->app_key
                ];

                $body = $data;

                $content = [
                    'head' => $head,
                    'body' => $body
                ];

                $res = $client->post($url, [
                    'json' => $content
                ]);

                dd($res->json());
            }
        }catch (\Exception $ex){

        }
    }

    public function checkStatus($order_id,$transaction_id){

        try{

        }catch (\Exception $ex){

        }

    }

}