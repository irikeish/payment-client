<?php


namespace lms\PaymentClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class PaymentClient
{

    private $base_url;
    private $app_key;
    const AKSHAMAALA_USER_ID = 'AKSHAMAALA:u132n231nti';
    const USER_TYPE_RETAILER='RETAILER';
    const USER_TYPE_FARMER='FARMER';
    const USER_TYPE_PRODUCT_SUPPLIER='PRODUCT_SUPPLIER';
    const USER_TYPE_OTHER_SUPPLIER='OTHER_SUPPLIER';
    const ORDER_TYPE_RETAILER_ORDER='RETAILER_ORDER';
    const ORDER_TYPE_AKSHAMAALA_ORDER='AKSHAMAALA_ORDER';
    const ORDER_TYPE_WALLET_TOPUP='WALLET_TOPUP';
    const USER_TYPE_SUPPLIER='SUPPLIER';
    const FUND_TRANSFER_TYPE_ADVANCE_PAYMENT='ADVANCE_PAYMENT';
    const FUND_TRANSFER_TYPE_ADJUST_PAYMENT='ADJUST_PAYMENT';
    const FUND_TRANSFER_TYPE_FINAL_PAYMENT='FINAL_PAYMENT';


    public function __construct($base_url="http://payment/api",$key="uwtqeijugsajdgw564e6e5tfhsaluwqiwqha"){
        $this->base_url = $base_url;
        $this->app_key = $key;
    }

    private function validateFundTransferRequest(array $data=[]){
        try{

            $validation_error = [];

            if(!isset($data['amount'])){
                array_push($validation_error,"amount is required");
            }
            if($data['amount']<0){
                array_push($validation_error,"amount cannot be negative");
            }
            if(!isset($data['order_id'])){
                array_push($validation_error,"order id is required");
            }
            if(!isset($data['user_id'])){
                array_push($validation_error,"user id is required");
            }
            if(!isset($data['user_type'])){
                array_push($validation_error,"user type is required");
            }
            if(!isset($data['total_amount'])){
                array_push($validation_error,"total amount is required");
            }
            if($data['total_amount']<0){
                array_push($validation_error,"total amount cannot be negative");
            }
            if($data['amount']>$data['total_amount']){
                array_push($validation_error,"amount cannot be greater than total amount");
            }
            if(sizeof($validation_error)>0){
                return ["status"=>false,"message"=>$validation_error];
            }

            return ['status'=>true];

        }catch (\Exception $ex){
            return ["status"=>false,"message"=>"validation exception, contact developer for support","ex"=>$ex];
        }
    }

    private function validatePaymentRequestData(array $data=[]){
        try{

            $validation_error = [];

            if(!isset($data['receiver_id'])){
                array_push($validation_error,"receiver_id is required");
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
                            array_push($validation_error,"payment breakup for ".$payment_by." has given amount: ".$amount." is not valid");
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

    private function validateGetBeneficiariesData(array $data=[]){
        try{

            $validation_error = [];

            if(!isset($data['user_type'])){
                array_push($validation_error,"user_type is required");
            }
            if(!isset($data['user_id'])){
                array_push($validation_error,"user_id is required");
            }
            if(sizeof($validation_error)>0){
                return ["status"=>false,"message"=>$validation_error];
            }

            return ['status'=>true];

        }catch (\Exception $ex){
            return ["status"=>false,"message"=>"validation exception, contact developer for support","ex"=>$ex];
        }
    }

    private function validateBeneficiariesData(array $data=[]){
        try{

            $validation_error = [];

            if(!isset($data['user_type'])){
                array_push($validation_error,"user_type is required");
            }
            if(!isset($data['user_id'])){
                array_push($validation_error,"user_id is required");
            }
            if(!isset($data['phone_number'])){
                array_push($validation_error,"phone_number is required");
            }
            if(!isset($data['account_number'])){
                array_push($validation_error,"account_number is required");
            }
            if(!isset($data['ifsc'])){
                array_push($validation_error,"ifsc is required");
            }
            if(!isset($data['name'])){
                array_push($validation_error,"name is required");
            }
            if(sizeof($validation_error)>0){
                return ["status"=>false,"message"=>$validation_error];
            }

            return ['status'=>true];

        }catch (\Exception $ex){
            return ["status"=>false,"message"=>"validation exception, contact developer for support","ex"=>$ex];
        }
    }

    private function validateDeleteBeneficiariesData(array $data=[]){
        try{

            $validation_error = [];

            if(!isset($data['account_id'])){
                array_push($validation_error,"account_id is required");
            }

            if(sizeof($validation_error)>0){
                return ["status"=>false,"message"=>$validation_error];
            }

            return ['status'=>true];

        }catch (\Exception $ex){
            return ["status"=>false,"message"=>"validation exception, contact developer for support","ex"=>$ex];
        }
    }

    /**
     * @param array $data = {
     *              receiver_id : string
     *              order_id : string
     *              payment_breakup : array[] = [{
     *                                               type: string,
     *                                               amount: string|numeric
     *                                          }]
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *                  wallet_balance : string
     *              }
     * @example { status: true, message: 'Money added to Wallet.', transaction_id:'323-1dca-cada-acc', transaction_type: 'input_wallet', transaction_status: { resultStatus: 'SUCCESS', message : 'input_wallet payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'input_wallet', status: 'success' }]} , wallet_balance: '2300'}
     */

    public function addMoneyToRetailerWallet(array $data=[]){
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

                $client = new Client();
                $url = $this->base_url.'/wallets/retailers/'.$data['receiver_id'].'/balance';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $data['payer_id']=self::AKSHAMAALA_USER_ID;
                $content = [
                    'json' => $data
                ];
                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                $messageStatus = '';
                $statusResult='';
                foreach( $payload_data['transaction_status'] as $type ) {
                    $statusResult=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $messageStatus = $type['payment_method'].' payment '.$type['status'];
                        $statusResult=strtoupper($type['status']);
                        break;

                    }
                }
                $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
                return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status,'wallet_balance'=>$payload_data['wallet_balance']];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>$ex->getMessage(),'ex'=>$ex];
        }
    }


    /**
     * @param array $data = {
     *              receiver_id : string
     *              order_id : string
     *              payment_breakup : array[] = [{
     *                                               type: string,
     *                                               amount: string|numeric
     *                                          }]
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *                  wallet_balance : string
     *              }
     * @example { status: true, message: 'Money added to Wallet.', transaction_id:'323-1dca-cada-acc', transaction_type: 'input_wallet', transaction_status: { resultStatus: 'SUCCESS', message : 'input_wallet payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'input_wallet', status: 'success' }]} , wallet_balance: '2300'}
     */
    public function addMoneyToSupplierWallet(array $data=[]){
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

                $client = new Client();
                $url = $this->base_url.'/wallets/suppliers/'.$data['receiver_id'].'/balance';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $data['payer_id']=self::AKSHAMAALA_USER_ID;
                $content = [
                    'json' => $data
                ];
                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                $messageStatus = '';
                $statusResult='';
                foreach( $payload_data['transaction_status'] as $type ) {
                    $statusResult=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $messageStatus = $type['payment_method'].' payment '.$type['status'];
                        $statusResult=strtoupper($type['status']);
                        break;

                    }
                }
                $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
                return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status,'wallet_balance'=>$payload_data['wallet_balance']];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>$ex->getMessage(),'ex'=>$ex];
        }
    }


    /**
     * @param array $data = {
     *              receiver_id : string
     *              order_id : string
     *              payment_breakup : array[] = [{
     *                                               type: string,
     *                                               amount: string|numeric
     *                                          }]
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *                  wallet_balance : string
     *              }
     * @example { status: true, message: 'Money added to Wallet.', transaction_id:'323-1dca-cada-acc', transaction_type: 'input_wallet', transaction_status: { resultStatus: 'SUCCESS', message : 'input_wallet payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'input_wallet', status: 'success' }]} , wallet_balance: '2300'}
     */
    public function addMoneyToFarmerWallet(array $data=[]){
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

                $client = new Client();
                $url = $this->base_url.'/payment';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $content = [
                    'json' => $data
                ];
                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                $messageStatus = '';
                $statusResult='';
                foreach( $payload_data['transaction_status'] as $type ) {
                    $statusResult=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $messageStatus = $type['payment_method'].' payment '.$type['status'];
                        $statusResult=strtoupper($type['status']);
                        break;

                    }
                }
                $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
                return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status,'wallet_balance'=>$payload_data['wallet_balance']];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>$ex->getMessage(),'ex'=>$ex];
        }
    }

    /**
     * @param array $data = {
     *              receiver_id : string
     *              order_id : string
     *              payment_breakup : array[] = [{
     *                                               type: string,
     *                                               amount: string|numeric
     *                                          }]
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'Transaction processed.', transaction_id:'323-1dca-cada-acc', transaction_type: 'cash', transaction_status: { resultStatus: 'SUCCESS', message : 'cash payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'cash', status: 'success' }]} }
     */
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

                // if($total_amount_pay != $data['total_amount']){
                //     return ['status'=>false,'message'=>"Total amount is not sum up with payment breakup"];
                // }

                $client = new Client();
                $url = $this->base_url.'/payment';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $content = [
                    'json' => $data
                ];
                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                $messageStatus = '';
                $statusResult='';
                foreach( $payload_data['transaction_status'] as $type ) {
                    $statusResult=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $messageStatus = $type['payment_method'].' payment '.$type['status'];
                        $statusResult=strtoupper($type['status']);
                        break;

                    }
                }
                $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
                return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>"some client error, contact to developer",'ex'=>$ex];
        }
    }


    /**
     * @param array $data = {
     *              receiver_id : string
     *              order_id : string
     *              payment_breakup : array[] = [{
     *                                               type: string,
     *                                               amount: string|numeric
     *                                          }]
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'Transaction processed.', transaction_id:'323-1dca-cada-acc', transaction_type: 'cash', transaction_status: { resultStatus: 'SUCCESS', message : 'cash payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'cash', status: 'success' }]} }
     */
    public function doRequestRetailer(array $data=[]){
        try{
            $data['receiver_id'] = self::AKSHAMAALA_USER_ID;
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

                // if($total_amount_pay != $data['total_amount']){
                //     return ['status'=>false,'message'=>"Total amount is not sum up with payment breakup"];
                // }

                $client = new Client();
                $url = $this->base_url.'/payment';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $content = [
                    'json' => $data
                ];
                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                $messageStatus = '';
                $statusResult='';
                foreach( $payload_data['transaction_status'] as $type ) {
                    $statusResult=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $messageStatus = $type['payment_method'].' payment '.$type['status'];
                        $statusResult=strtoupper($type['status']);
                        break;

                    }
                }
                $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
                return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>"some client error, contact to developer",'ex'=>$ex];
        }
    }


    /**
     * @param array $data = {
     *              amount : string,
     *              order_id : string,
     *              user_id : string,
     *              user_type : string,
     *              total_amount : string
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'Fund transfer requested.', transaction_id:'323-1dca-cada-acc', transaction_type: 'fund_transfer', transaction_status: { resultStatus: 'REQUESTED', message : 'fund transfer payment requested', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'fund_transfer', status: 'requested' }]} }
     */
    public function requestFundTransfer(array $data=[]){
        try{
                $validation = $this->validateFundTransferRequest($data);
                if(!$validation['status']){
                    return $validation;
                }
                $client = new Client();
                $url = $this->base_url.'/fund-transfer-request';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $content = [
                    'json' => $data
                ];
                $res = $client->post($url, $content);
                $response_data = json_decode($res->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                $messageStatus = '';
                $statusResult='';
                foreach( $payload_data['transaction_status'] as $type ) {
                    $statusResult=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $messageStatus = $type['payment_method'].' payment '.$type['status'];
                        $statusResult=strtoupper($type['status']);
                        break;

                    }
                }
                $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
                return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>"some client error, contact to developer",'ex'=>$ex];
        }
    }


    /**
     * @param array $data = {
     *              order_id : string,
     *              transaction_id : string
     *              }
     * @return array [] = {
     *                  resultStatus: string,
     *                  message : string
     *                  status_response : array = [{
     *                                                 transaction_id : string,
     *                                                 payment_method : string,
     *                                                 status : string
     *                                            }]
     *              }
     * @example {  message: 'Transaction status fetched.', resultStatus: 'SUCCESS',status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'fund_transfer', status: 'success' }] }
     */
    public function checkStatus(array $data=[]){

        try{

            if (!$data['order_id'] || !$data['transaction_id']) {
                throw new \Exception("Invalid Request parameters");
            } else {

                $client = new Client();
                $url = $this->base_url.'/status';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $head = [];
                $body = $data;

                $content = [
                    'json' => ($data)
                ];
                $res = $client->post($url, $content);
                $response_str = $res->getBody()->getContents();

                $response_data = json_decode($response_str,true);

                $payload = $response_data['payload'];

                $payload_data = $payload['data'];

                $message = '';
                $status='';
                foreach( $payload_data as $type ) {
                    $status=strtoupper($type['status']);
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                        $message = $type['payment_method'].' payment '.$type['status'];
                        $status=strtoupper($type['status']);
                        break;

                    }
                }

                return ['resultStatus'=>$status,'message'=>$message,'status_response'=>$payload_data];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              driver_response : json,
     *              transaction_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  resultStatus: string,
     *                  status_response : array = [{
     *                                             payment_method : string,
     *                                             status : string
     *                                            }]
     *              }
     * @example { message: '', resultStatus: 'SUCCESS', status_response:[{ payment_method : 'fund_transfer', status: 'requested' }] }
     */
    public function validateStatus(array $data=[]){

        try{

            if (!$data['driver_response'] || !$data['transaction_id']) {
                throw new \Exception("Invalid Request parameters");
            } else {

                $client = new Client();
                $url = $this->base_url.'/validate';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $head = [];
                $body = $data;
                $content = [
                    'json' => ($data)
                ];
                $res = $client->post($url, $content);
                $response_str = $res->getBody()->getContents();

                $response_data = json_decode($response_str,true);

                $payload = $response_data['payload'];

                $payload_data = $payload['data'];
                $status='';
                foreach( $payload_data as $type ) {
                    $status=strtoupper($type['status']);
                    $message = $type['message'];
                    if ( $type['status'] == 'failed' || $type['status'] == 'pending') {
                        $status=strtoupper($type['status']);
                        break;

                    }
                }

                return ['resultStatus'=>$status,'message'=>$message,'status_response'=>['status'=>$payload_data[0]['status'],'payment_method'=>$payload_data[0]['payment_method'] ]];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              user_id : string,
     *              machine_type : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  resultStatus: string,
     *                  status_response : array = [{
     *                                             payment_method : string,
     *                                             status : string
     *                                            }]
     *              }
     * @example { status: true, message: '', data:{ user_id : '1', machine_type: 'paytm', machine_tid: '323uaocan' } }
     */
    public function registerMachine(array $data =[]) {

        try{

            if (!isset($data['user_id'])) {
                throw new \Exception("User id is required.");
            }

            if (!isset($data['machine_type'])) {
                throw new \Exception("Machine type is required.");
            }

            $user_id = $data['user_id'];
            $client = new Client();
            $url = $this->base_url.'/registerMachine';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);

            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);

            $payload = $response_data['payload'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload['data']];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }
        catch (\Exception $ex){
            return ["status"=>false,"message"=>$ex->getMessage(),'ex'=>$ex];
        }

    }


    /**
     * @param array $data = {
     *              user_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  resultStatus: string,
     *                  status_response : array = [{
     *                                             payment_method : string,
     *                                             status : string
     *                                            }]
     *              }
     * @example { status: true, message: '', data:{  machine_type: 'paytm', machine_tid: '323uaocan' } }
     */
    public function getMachine(array $data = []) {
        try{
            $user_id = $data['user_id'];
            $client = new Client();
            $url = $this->base_url.'/machine';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              order_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            transaction_id : string,
     *                            order_id : string,
     *                            amount : string,
     *                            status : string
     *                          }
     *              }
     * @example { status: true, message: 'status fetched', data:{  transaction_id: '1121k2ndo', order_id: '332312bu', amount :'12', status: 'success' } }
     */
    public function fundTransferStatus(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/fund-transfers/status';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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

    public function revert(array $data=[]){

        try{

            if (!$data['transaction_ids']) {
                throw new \Exception("Invalid Request parameters");
            } else {
                $client = new Client();
                $url = $this->base_url.'/revert';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);

                $head = [];
                $body = $data;

                $content = [
                    'json' => ($data)
                ];
                $res = $client->post($url, $content);

                $response_str = $res->getBody()->getContents();

                $response_data = json_decode($response_str,true);

                $payload = $response_data['payload'];


                $message = '';
                $status='';

                // var_dump($response_str);
                return ['resultStatus'=>$response_data['status'],'message'=>$payload['message'],'status_response'=>$payload['data']];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
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


    public function revertStatus(array $data=[]){

        try{

            if (!$data['transaction_ids']) {
                throw new \Exception("Invalid Request parameters");
            } else {
                $client = new Client();
                $url = $this->base_url.'/revertStatus';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);

                $head = [];
                $body = $data;

                $content = [
                    'json' => ($data)
                ];
                $res = $client->post($url, $content);

                $response_str = $res->getBody()->getContents();

                $response_data = json_decode($response_str,true);

                $payload = $response_data['payload'];


                $message = '';
                $status='';


                return ['resultStatus'=>$response_data['status'],'message'=>$payload['message'],'status_respones'=>$payload['data']];
            }

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment service connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              retailer_id : string
     *              }
     * @return array [] = {
     *                  code : integer
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            balance : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'retailer's account and wallet created', data:{  balance: '0' } }
     */
    public function addRetailerWallet(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/wallets/retailers';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data=$payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code'],'data'=>$payload['data']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              supplier_id : string
     *              }
     * @return array [] = {
     *                  code : integer
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            balance : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'supplier's account and wallet created', data:{  balance: '0' } }
     */
    public function addSupplierWallet(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/wallets/suppliers';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data=$payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code'],'data'=>$payload['data']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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



    /**
     * @param array $data = {
     *              farmer_id : string,
     *              retailer_id : string
     *              }
     * @return array [] = {
     *                  code : integer
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            balance : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'farmer's wallet created and linked to retailer', data:{  balance: '0' } }
     */
    public function addFarmerWallet(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/wallets/farmers';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message'],'code'=>$response_data['code'],'data'=>$payload['data']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              transaction_id : string,
     *              otp : string
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'OTP verified and wallet updated.', transaction_id:'323-1dca-cada-acc', transaction_type: 'input_wallet', transaction_status: { resultStatus: 'SUCCESS', message : 'input_wallet payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'input_wallet', status: 'success' }]}}
     */
    public function verifyOtpInputWallet(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/input-wallet/verify-otp';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $response_data['payload']['message'];

            $messageStatus = '';
            $statusResult='';
            foreach( $payload_data['transaction_status'] as $type ) {
                $statusResult=strtoupper($type['status']);
                if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                    $messageStatus = $type['payment_method'].' payment '.$type['status'];
                    $statusResult=strtoupper($type['status']);
                    break;

                }
            }
            $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
            return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status,'wallet_balance'=>$payload_data['wallet_balance']];
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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

    /**
     * @param array $data = {
     *              transaction_id : string,
     *              otp : string
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'OTP verified and payment done.', transaction_id:'323-1dca-cada-acc', transaction_type: 'unnati_credit', transaction_status: { resultStatus: 'SUCCESS', message : 'unnati_credit payment successful', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'unnati_credit', status: 'success' }]}}
     */
    public function verifyOtpChottaCredit(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/chotta-credit/verify-otp';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $response_data['payload']['message'];

            $messageStatus = '';
            $statusResult='';
            foreach( $payload_data['transaction_status'] as $type ) {
                $statusResult=strtoupper($type['status']);
                if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                    $messageStatus = $type['payment_method'].' payment '.$type['status'];
                    $statusResult=strtoupper($type['status']);
                    break;

                }
            }
            $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
            return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status,'wallet_balance'=>$payload_data['wallet_balance']];
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              transaction_id : string
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'OTP resent successfully.', transaction_id:'323-1dca-cada-acc', transaction_type: 'input_wallet', transaction_status: { resultStatus: 'PENDING', message : 'input_wallet payment pending', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'input_wallet', status: 'pending' }]}}
     */
    public function resendOtpInputWallet(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/input-wallet/resend-otp';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $response_data['payload']['message'];

            $messageStatus = '';
            $statusResult='';
            foreach( $payload_data['transaction_status'] as $type ) {
                $statusResult=strtoupper($type['status']);
                if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                    $messageStatus = $type['payment_method'].' payment '.$type['status'];
                    $statusResult=strtoupper($type['status']);
                    break;

                }
            }
            $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
            return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status];
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              transaction_id : string
     *              }
     * @return array [] = {
     *                  status : boolean
     *                  message : string
     *                  transaction_id : string
     *                  transaction_type : string
     *                  transaction_status : array = [{
     *                                                  resultStatus: string,
     *                                                  message : string,
     *                                                  status_response : array = [{
     *                                                                             transaction_id : string,
     *                                                                             payment_method : string,
     *                                                                             status : string
     *                                                                            }]
     *                                               }]
     *              }
     * @example { status: true, message: 'OTP resent successfully', transaction_id:'323-1dca-cada-acc', transaction_type: 'unnati_credit', transaction_status: { resultStatus: 'PENDING', message : 'unnati_credit payment pending', status_response:[{ transaction_id : '323-1dca-cada-acc' , payment_method : 'unnati_credit', status: 'pending' }]}}
     */
    public function resendOtpChottaCredit(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/chotta-credit/resend-otp';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $response_data['payload']['message'];

            $messageStatus = '';
            $statusResult='';
            foreach( $payload_data['transaction_status'] as $type ) {
                $statusResult=strtoupper($type['status']);
                if ( $type['status'] == 'failed' || $type['status'] == 'pending') {

                    $messageStatus = $type['payment_method'].' payment '.$type['status'];
                    $statusResult=strtoupper($type['status']);
                    break;

                }
            }
            $transaction_status=['resultStatus'=>$statusResult,'message'=>$messageStatus,'status_response'=>$payload_data['transaction_status']];
            return ['status'=>$response_data['status'],'message'=>$message,'transaction_id'=>implode(':',$payload_data['transaction_id']),'transaction_type'=>implode(':',$payload_data['transaction_type']),'transaction_status'=>$transaction_status];
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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

    /**
     * @param array $data = {
     *              retailer_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            balance : string,
     *                            retailer_id : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'retailer balance fetched.', data:{  balance: '123', retailer_id: '845' } }
     */
    public function getBalanceRetailer(array $data = []) {
        try{
            // var_dump($this->app_key);
            // die();
            $client = new Client();
            $url = $this->base_url.'/wallets/retailers/'.$data['retailer_id'];
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $res = $client->get($url);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              retailer_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            transactions : array[{
     *                                  from: string,
     *                                  to: string,
     *                                  amount:  string,
     *                                  meta: json,
     *                                  transfer_uuid: string,
     *                                  created_at: string,
     *                                  type: string
     *                              }]
     *                          }
     *              }
     * @example { status: true, message: 'retailer transactions fetched.', data:{ transactions:[{from:"RETAILER:3434",to:"FARMER:4323",amount:"231"}] }
     */
    public function getTransactionsRetailer(array $data = []) {
        try{
            // var_dump($this->app_key);
            // die();
            $client = new Client();
            $url = $this->base_url.'/wallets/retailers/'.$data['retailer_id'].'/transactions';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $res = $client->get($url);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              supplier_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            balance : string,
     *                            supplier_id : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'supplier balance fetched.', data:{  balance: '123', supplier_id: '845' } }
     */
    public function getBalanceSupplier(array $data = []) {
        try{
            // var_dump($this->app_key);
            // die();
            $client = new Client();
            $url = $this->base_url.'/wallets/suppliers/'.$data['supplier_id'];
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->post($url, $content);

            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              supplier_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            balance : string,
     *                            supplier_id : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'supplier main-account balance fetched.', data:{  balance: '123', supplier_id: '845' } }
     */
    public function getMainAccountBalanceSupplier(array $data = []) {
        try{
            // var_dump($this->app_key);
            // die();
            $client = new Client();
            $url = $this->base_url.'/wallets/suppliers/main-account/'.$data['supplier_id'];
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->get($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              order_id : string,
     *              supplier_id : string
     *              }
     * @return array [] = {
     *                  code : integer,
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            success : string,
     *                            pending : string,
     *                            requested : string
     *                          }
     *              }
     * @example { code : 201, status: true, message: '.', data:{  success: '123', pending: '845', requested: '3121' } }
     */
    public function getSupplierFundTransferByOrderId(array $data = []) {
        try{
            // var_dump($this->app_key);
            // die();
            $client = new Client();
            $url = $this->base_url.'/suppliers/'.$data['supplier_id'].'/fund-transfer-history/'.$data['order_id'];
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->get($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              farmer_ids : string []
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            farmers : array[{}],
     *                            farmer_ids : string []
     *                          }
     *              }
     * @example { code : 201, status: true, message: 'farmer balance fetched.', data:{  "farmers": { "122230": { "mapped_retailer_ids": ["535"],"balance": "4300","farmer_id": 122230}},"farmer_ids": [122230] } }
     */
    public function getBalanceFarmer(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/wallets/balance/farmers';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $content = [
                'json' => $data
            ];
            $res = $client->post($url,$content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              farmer_id : string
     *              }
     * @return array [] = {
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                            transactions : array[{
     *                                  from: string,
     *                                  to: string,
     *                                  amount:  string,
     *                                  meta: json,
     *                                  transfer_uuid: string,
     *                                  created_at: string,
     *                                  type: string
     *                              }]
     *                          }
     *              }
     * @example { status: true, message: 'farmer transactions fetched.', data:{ transactions:[{from:"FARMER:3434",to:"RETAILER:4323",amount:"231"}] }
     */
    public function getTransactionsFarmer(array $data = []) {
        try{
            $client = new Client();
            $url = $this->base_url.'/wallets/farmers/'.$data['farmer_id'].'/transactions';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);

            $res = $client->get($url);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              user_type : string,
     *              user_id : string,
     *              phone_number : string,
     *              account_number : string,
     *              ifsc : string,
     *              name : string,
     *              }
     * @return array [] = {
     *                  code : integer,
     *                  message : string
     *                  status: boolean,
     *                  data : array []
     *              }
     * @example { code : 201, status: true, message: 'User created and bank details registered successfully.', data:{} }
     */
    public function addUserAndBeneficiary(array $data = []) {

        try{
            $validation = $this->validateBeneficiariesData($data);
            if (!$validation['status']) {
                return $validation;
            } else {
                $client = new Client();
                $url = $this->base_url.'/beneficiaries';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $head = [];
                $body = $data;
                $content = [
                    'json' => ($data)
                ];
                $res = $client->post($url, $content);

                $response_str = $res->getBody()->getContents();
                $response_data = json_decode($response_str,true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];
            }
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              user_type : string,
     *              user_id : string,
     *              }
     * @return array [] = {
     *                  code : integer,
     *                  message : string
     *                  status: boolean,
     *                  data : array = {
     *                                  account_id: string,
     *                                  user_id: string,
     *                                  user_type: string,
     *                                  account_status: string,
     *                                  account_verification: string,
     *                                  account_number: string,
     *                                  ifsc: string
     *                                 }
     *              }
     * @example { code : 201, status: true, message: 'Beneficiary detailed fetched successfully.', data:{account_id:'1',user_id:'22',user_type:'VENDOR',account_status:'success',account_verification:'success',account_number:'131231312313',ifsc:'PYTM0123456'} }
     */
    public function getBeneficiary(array $data = []) {

        try{
            $validation = $this->validateGetBeneficiariesData($data);
            if (!$validation['status']) {
                return $validation;
            } else {
                $client = new Client();

                $url = $this->base_url.'/beneficiaries/'.$data['user_type'].'/'.$data['user_id'];
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $res = $client->get($url);

                $response_str = $res->getBody()->getContents();
                $response_data = json_decode($response_str,true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];
            }
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              account_id : string
     *              }
     * @return array [] = {
     *                  code : integer,
     *                  message : string
     *                  status: boolean,
     *                  data : array = []
     *              }
     * @example { code : 201, status: true, message: 'Beneficiary deleted.', data:{} }
     */
    public function deleteBeneficiary(array $data = []) {

        try{
            $validation = $this->validateDeleteBeneficiariesData($data);
            if (!$validation['status']) {
                return $validation;
            } else {
                $client = new Client();
                $url = $this->base_url.'/beneficiaries';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $head = [];
                $body = $data;
                $content = [
                    'json' => ($data)
                ];
                $res = $client->delete($url, $content);

                $response_str = $res->getBody()->getContents();
                $response_data = json_decode($response_str,true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];
            }
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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


    /**
     * @param array $data = {
     *              account_id : string
     *              }
     * @return array [] = {
     *                  code : integer,
     *                  message : string
     *                  status: boolean,
     *                  data : array = []
     *              }
     * @example { code : 201, status: true, message: 'Verification Initiated.', data:{} }
     */
    public function verifyBeneficiary(array $data = []) {

        try{
            $validation = $this->validateDeleteBeneficiariesData($data);
            if (!$validation['status']) {
                return $validation;
            } else {
                $client = new Client();
                $url = $this->base_url.'/beneficiaries/verify';
                $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
                $head = [];
                $body = $data;
                $content = [
                    'json' => ($data)
                ];
                $res = $client->post($url, $content);

                $response_str = $res->getBody()->getContents();
                $response_data = json_decode($response_str,true);
                $payload = $response_data['payload'];
                $payload_data = $payload['data'];
                $message = $payload['message'];
                return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];
            }
        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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
    /*** --- Get Supplier Wallet Transaction (Abhilash) **/
    public function getSupplierWalletTransactions(array $data = []) {
        try{

            $client = new Client();
            $url = $this->base_url.'/wallets/supplier/'.$data['supplier_id'].'/transactions';
            $client->setDefaultOption('headers', [ 'Content-Type' => 'application/json','app-key'=>$this->app_key ]);
            $head = [];
            $body = $data;
            $content = [
                'json' => ($data)
            ];
            $res = $client->get($url, $content);
            $response_str = $res->getBody()->getContents();
            $response_data = json_decode($response_str,true);
            $payload = $response_data['payload'];
            $payload_data = $payload['data'];
            $message = $payload['message'];
            return ['code'=>$response_data['code'],'status'=>$response_data['status'],'message'=>$message,'data'=>$payload_data];

        }catch (ClientException $ex){
            if($ex->hasResponse()){
                $response_data = json_decode($ex->getResponse()->getBody()->getContents(),true);
                $payload = $response_data['payload'];
                return ['status'=>false,"message"=>$payload['message']];
            }
            return ['status'=>false,'message'=>"some payment client connection error with no error response"];
        }catch (RequestException $ex){
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

}