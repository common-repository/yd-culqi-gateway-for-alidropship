<?php
/**
 * Class PurchaseRequest
 *
 * The class requests and processes the transaction data, in case of a successful transaction status,
 * sets the order status to "reserved", sends a purchase email notification to the client about the successful order status with order details,
 * and then redirects the client to the thank you page
 *
 */
    
namespace Culqi\Message;

use ADS;
use ads\adsNotification;
use Gate\Common\Request;
use Gate\Payment;

class PurchaseRequest extends Request {

    protected $endpoint = 'https://api.culqi.com/v2/charges';

    public function getPayCur() {
        
        return $this->getParameter('pay_currency');
    }

    public function getSecretKey() {

        return $this->getParameter('secretKey');
    }

    public function getToken() {

        return isset( $_POST['tokenId'] ) ? esc_attr( $_POST['tokenId'] ) : '';
    }

    /**
     * Sends data to receive payment information
     *
     * @return mixed
     */
    public function send() {
    
        if( ! empty( $this->getPayCur() ) && $this->getPayCur() != $this->getCurrency() ) {
            $this->setAmount(
                ads_price_convert_currents(
                    $this->getAmount(),
                    $this->getCurrency(),
                    $this->getPayCur()
                )
            );
        
            $this->setCurrency( $this->getPayCur() );
        }

        $data = $this->call_charge();

        if( isset( $data['object'] ) && ( $data['object'] == 'charge' ) ) {

            $request = $this->call_retrieve($data['id']);

            if ( ! $request ) {

                do_action('add_log', ['code' => '40003', 'details' => $data],
                    ads_error_message('40003'), 'order', 'danger', 0, $this->getHash());

                return false;
            }

            $this->setTransactionId( $request['id'] );

            $pay  = new Payment();
            $data = $pay->findOne( $this->getHash() );

            $pay->updateDetails( $data->id, $request, 'paid' );
            $pay->updateDate( $data->id, date('Y-m-d H:i:s') );
            $pay->updateTnxId( $this->getTransactionId(), $request['id'] );

            ads_set_used_coupon( $data->discount_code );

            unset( ADS::session()->hash );

            global $adsBasket;

            $adsBasket->clear();

            do_action( 'add_log', '', ads_error_message('20001'), 'payment', 'success' );
            do_action( 'ads_pay_success', $data );

            $sm = new adsNotification();
            $sm->sendOrderMail( $data );
            
            ADS::session()->set( 'ads_success_hash', $this->getHash() );

            return [ 'redirect' => home_url('/thankyou/') . '?tmpo=&fail=no&message=20001&h=' . $this->getHash() ];
        }

        do_action( 'add_log', [ 'code' => '40003', 'details' => $data ],
            ads_error_message( '40003' ), 'order', 'danger', 0, $this->getHash() );

        return [
            'error' => __( 'Transaction could not been completed', 'cg' ),
        ];
    }

    /**
     * Obtaining information about a transaction by token
     *
     * @return array|false|mixed|string
     */
    private function call_charge() {

        $data = [
            'amount'        => $this->getAmount()*100,
            'currency_code' => $this->getCurrency(),
            'email'         => $this->getEmail(),
            'source_id'     => $this->getToken()
        ];

        $request = $this->sendData( json_encode($data), [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getSecretKey()
        ], 'POST');

        if( is_array( $request ) )
            return $request;

        $request = json_decode( $request, true );

        if( isset( $request['object'] ) && $request['object'] == 'error' ) {

            $_POST['ads-error'] = [
                'merchant_message' => $request['merchant_message'],
                'user_message'     => $request['user_message']
            ];

            return false;
        }

        return $request;
    }

    /**
     * Getting transaction data by transaction ID
     *
     * @param $id
     * @return array|false|mixed|string
     */
    private function call_retrieve( $id ) {

        $this->endpoint .= '/' . $id;

        $request = $this->sendData( [], [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getSecretKey()
        ], 'GET');

        if( is_array( $request ) )
            return $request;

        $request = json_decode( $request, true );

        if( isset( $request['object'] ) && $request['object'] == 'error' ) {

            $_POST['ads-error'] = [
                'merchant_message' => $request['merchant_message'],
                'user_message'     => $request['user_message']
            ];

            return false;
        }

        return $request;
    }
}
