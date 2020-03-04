<?php


namespace lms\PaymentClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Rakit\Validation\Validator;

class PaymentClient
{

    private $base_url;
    private $app_key;

    public function __construct($base_url="http://payment/api",$key="uwtqeijugsajdgw564e6e5tfhsaluwqiwqha"){
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

                $client = new Client(['headers' => [ 'Content-Type' => 'application/json',"app_key"=>$this->app_key ]]);
                $url = $this->base_url.'/payment';

                $content = [
                    'body' => json_encode($data)
                ];

                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                return ['status'=>true,'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id'])];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>"some client error, contact to developer",'ex'=>$ex];
        }
    }

    public function checkStatus($order_id,$transaction_id){

        try{

        }catch (\Exception $ex){

        }

    }

}