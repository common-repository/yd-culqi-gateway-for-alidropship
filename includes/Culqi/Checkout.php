<?php
/**
 * Class Checkout
 *
 * The class generates default parameters, sets the mail and id parameters, creates requests to other classes
 *
 * @package Culqi
 */
    
    
namespace Culqi;

use Gate\Common\Gateway;

class Checkout extends Gateway {
    
    public function __construct() {
        
        parent::__construct('culqi');
    }
    
    public function getDefaultParameters() {
        
        return [
            'secretKey'    => '',
            'apiPub'       => '',
            'source'       => '',
            'currency'     => 'USD',
            'image'        => '',
            'amount'       => 0.00,
            'pay_currency' => '',
        ];
    }
    
    /**
     * purchase
     */
    public function purchase() {

        $request = $this->createRequest('\Culqi\Message\PurchaseRequest');

        if( ! isset( $request['redirect'] ) ) {
            return $request;
        }

        return $request;
    }
}
