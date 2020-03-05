<?php


namespace lms\PaymentClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class PaymentClient
{

    private $base_url;
    private $app_key;

    public function __construct($base_url="http://payment/api",$key="uwtqeijugsajdgw564e6e5tfhsaluwqiwqha"){
        $this->base_url = $base_url;
        $this->app_key = $key;
    }

    private function validatePaymentRequestData(array $data=[]){
        try{

            $validation_error = [];

            if(!isset($data['user_id'])){
                array_push($validation_error,"user_id is required");
            }
            if(!isset($data['order_id'])){
                array_push($validation_error,"order_id is required");
            }

            if(!isset($data['payment_breakup'])){
                array_push($validation_error,"payment breakup is required");
            }else  if(!is_array($data['payment_breakup'])){
                array_push($validation_error,"payment_breakup should be array");
            }else if(is_array($data['payment_breakup'])){

                $payment_breakup = $data['payment_breakup'];
                if(sizeof($payment_breakup) == 0){
                    array_push($validation_error,"payment_breakup can not be empty array");
                }
                foreach ($payment_breakup as $payment_data){

                    if(!isset($payment_data['type']) || !isset($payment_data['amount'])){
                        array_push($validation_error,"payment breakup tuple is not a valid, it should have valid type and amount");
                    }else {
                        $payment_by = $payment_data['type'];
                        $amount = $payment_data['amount'];

                        if(!is_numeric($amount) || ( is_numeric($amount) && $amount < 0)){
                            array_push($validation_error,"payment breakup for".$payment_by."has given amount: ".$amount." is not valid");
                        }
                    }

                }
            }

            if(!isset($data['total_amount'])){
                array_push($validation_error,"total amount is required");
            }else if(!is_numeric($data['total_amount']) || ( is_numeric($data['total_amount']) && $data['total_amount'] < 0) ){
                array_push($validation_error,"total amount is not a valid amount");
            }

            if(sizeof($validation_error)>0){
                return ["status"=>false,"message"=>$validation_error];
            }

            return ['status'=>true];

        }catch (\Exception $ex){
            return ["status"=>false,"message"=>"validation exception, contact developer for support","ex"=>$ex];
        }
    }

    public function doRequest(array $data=[]){
        try{

            $validation = $this->validatePaymentRequestData($data);

            if (!$validation['status']) {
                return $validation;
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