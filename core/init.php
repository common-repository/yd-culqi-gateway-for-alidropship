<?php

use Culqi\Checkout;

/**
 * Setting the path to the plugin
 *
 * @param $className
 */
function cg_autoload( $className ) {
    
    $className = ltrim( $className, '\\' );
    $fileName  = '';
    
    if ( $lastNsPos = strrpos( $className, '\\' ) ) {
        $namespace = substr( $className, 0, $lastNsPos );
        $className = substr( $className, $lastNsPos + 1 );
        
        $fileName = str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
    }
    
    $fileName .= $className . '.php';
    
    $file = CG_PATH . 'includes/' . $fileName;
    
    if ( file_exists( $file ) ) {
        require( $file );
    }
}
spl_autoload_register('cg_autoload');

/**
 * Definition of all accepted currencies
 *
 * @param $pay_currency
 * @return mixed
 */
function cg_currencies( $pay_currency ) {
    
    $foo     = [];
    $pgcur   = [ 'PEN', 'USD' ];
    $options = $pay_currency[ 'options' ];
    
    foreach( $options as $key => $val ) {
        if( in_array( $key, $pgcur ) )
            $foo[ $key ] = $val;
    }
    
    $pay_currency['options'] = $foo;
    
    return $pay_currency;
}
add_filter( 'cg_currencies', 'cg_currencies' );

/**
 * Formation of a list of payment gateway settings
 *
 * @param $args
 * @return mixed
 */
function cg_list_gateway_settings( $args ) {

    $args['culqi'] = [
        'title'  => [
            'call'    => 'sanitize_text_field',
            'default' => '',
            'type'    => 'text',
            'label'   => __( 'Title', 'cg' ),
            'help'    => __( "The title is displayed on checkout page instead of the original 'Credit Card'.", 'cg' )
        ],
        'type'   => [
            'call'    => 'sanitize_text_field',
            'default' => 'culqi',
            'type'    => 'hidden'
        ],
        'status' => [
            'call'    => 'intval',
            'default' => 0,
            'type'    => 'switcher',
            'label'   => __( 'Enable Culqi payment option', 'cg' )
        ],
        'pay_currency' => apply_filters( 'cg_currencies', [
            'call'    => 'sanitize_text_field',
            'default' => ADS_CUR_DEF,
            'options' => ads_payment_cur_list(),
            'type'    => 'select',
            'label'   => __( 'Currency', 'cg' ),
            'help'    => __( 'If the default currency is different from the currency of the payment gateway, select from the available options.', 'cg' )
        ] ),
        'image' => [
            'call'    => 'sanitize_text_field',
            'default' => '',
            'type'    => 'text',
            'label'   => __( 'Logo', 'cg' ),
            'help'    => __( 'Enter logo URL.', 'cg' )
        ],
        'shopName'  => [
            'call'    => 'sanitize_text_field',
            'default' => '',
            'type'    => 'text',
            'label'   => __( 'Shop name', 'cg' ),
            'help'    => ''
        ],
        'description'  => [
            'call'    => 'sanitize_text_field',
            'default' => '',
            'type'    => 'text',
            'label'   => __( 'Shop description', 'cg' ),
            'help'    => ''
        ],
        'apiPub' => [
            'call'    => 'sanitize_text_field',
            'default' => '',
            'type'    => 'text',
            'label'   => __( 'Public key', 'cg' ),
            'help'    => __( 'Enter publishable key from your account.', 'cg' )
        ],
        'secretKey' => [
            'call'    => 'sanitize_text_field',
            'default' => '',
            'type'    => 'text',
            'label'   => __( 'Apps key', 'cg' ),
            'help'    => __( 'Enter secret key from your account.', 'cg' )
        ]
    ];
    
    return $args;
}
add_filter( 'ads_list_gateway_settings', 'cg_list_gateway_settings' );

/**
 * Setting the name of the payment gateway in the list of payment gateways
 *
 * @param $args
 * @return mixed
 */
function cg_list_gateway_names( $args ) {
    
    $args['culqi'] = 'Culqi';
    
    return $args;
}
add_filter( 'ads_list_gateway_names', 'cg_list_gateway_names' );

/**
 * Specifying the path to the checkout class to access the rest of the classes
 *
 * @param $args
 * @return mixed
 */
function cg_culqi_path( $args ) {
    
    $args['culqi'] = '\Culqi\Checkout';
    
    return $args;
}
add_filter( 'ads_gateways_path', 'cg_culqi_path' );

/**
 * Creation and output of a culqi payment form for entering customer card data
 */
function cg_gateway_culqi() {

    $setting = ADS_CARD_SETTINGS;

    ?>

    <script type="text/javascript" src="https://checkout.culqi.com/js/v3"></script>
    <script type="text/javascript">
        function waitCulqi() {
            if ( window.jQuery ) {
                jQuery(function($){
                    let culqi_launch = 0,
                        $purchase = $('[name="ads_checkout"]'),
                        $form     = $('#form_delivery');


                    function getBasketData() {

                        $.ajax({
                            url      : alidAjax.ajaxurl,
                            type     : 'POST',
                            dataType : 'json',
                            async    : true,
                            data     : {
                                action      : 'cg_action_gateway',
                                ads_actions : 'get_orders',
                                discount    : $('#discount').val()
                            },
                            success  : function (data) {

                                if( data.hasOwnProperty('cur_payment_price') ) {

                                    const price    = Math.trunc(data.cur_payment_price.toFixed(2)*100),
                                          currency = data.cur_payment_simbol;

                                    if ( price < 300 ){

                                        alert('Your \'amount\' post variable should be between 3.00 and 9999.00 ' + currency);

                                        if ($purchase.hasClass('btn-processed')) {
                                            $purchase.removeClass('btn-processed');
                                            $purchase.removeClass('checkout-spinner');
                                            $purchase.prop( 'disabled', false );
                                        }
                                    } else {

                                        Culqi.publicKey = '<?php echo $setting['apiPub'] ?>';

                                        Culqi.options({
                                            lang: 'auto',
                                            modal: true,
                                            installments: false,
                                            style: {
                                                logo       : '<?php echo $setting['image'] ?>',
                                                maincolor  : '#FF8D38',
                                                buttontext : '#ffffff',
                                                maintext   : '#4A4A4A',
                                                desctext   : '#4A4A4A'
                                            }
                                        });

                                        Culqi.settings({
                                            title       : '<?php echo $setting['shopName'] ?>',
                                            currency    : currency,
                                            description : '<?php echo $setting['description'] ?>',
                                            amount      : price
                                        });

                                        Culqi.open();
                                    }
                                }
                            }
                        });
                    }

                    $form.on( 'submit', function(e) {

                        const c   = $('.js-invalid_empty').length,
                              sel = $('.hasSelect.error-empty:not(.box-hidden)').length;

                        if( $form.find('#cc').is(':checked') ) {

                            if( c === 0 && sel === 0 && ! $purchase.hasClass('readyCharge') ) {
                                e.preventDefault();
                                $purchase.addClass('checkout-spinner').prop( 'disabled', true );
                                getBasketData();
                                culqi_launch = 1;
                            } else {
                                if( ! culqi_launch ) {
                                    e.preventDefault();
                                    setTimeout( () => {
                                        $purchase.removeClass('btn-processed');
                                    },1000 )
                                }
                            }
                        }
                    });
                })

            } else { window.setTimeout( waitCulqi, 200 ); }
        }

        function culqi() {

            let $purchase = jQuery('[name="ads_checkout"]'),
                $form     = jQuery('#form_delivery'),
                $         = jQuery;

            if (Culqi.token) {
                $form.append( $('<input type="hidden" name="tokenId" />' ).val(Culqi.token.id) );
                $purchase.prop('disabled', false ).addClass('readyCharge').click();
            } else {
                $purchase.removeClass('checkout-spinner').prop( 'disabled', false );
                return false;
            }
        }

        const culqiListener = setInterval(function(){
            let elem = document.activeElement;
            if(elem && elem.id == 'culqi_checkout_frame'){
                let $         = jQuery
                let $purchase = $('[name="ads_checkout"]')

                if ($purchase.hasClass('btn-processed')) {
                    $purchase.removeClass('btn-processed');
                    $purchase.removeClass('checkout-spinner');
                    $purchase.prop( 'disabled', false );
                }
                clearInterval(culqiListener);
            }
        }, 100);

        waitCulqi();
    </script>
    <?php
    echo apply_filters(
        'ads_payment_icon',
        '<img class="js-complete_order" src="' . ADS_URL . '/src/images/payment/info.png">'
    );
    ?>
    <div class="text-info">
        <?php echo apply_filters(
            'ads_credit_card_bottom_text',
            __( 'Click proceed to pay to complete your order.', 'ads' )
        ) ?>
    </div>
    <?php
}
remove_action( 'ads_gateway_culqi', 'ads_gateway_culqi' );
add_action( 'ads_gateway_culqi', 'cg_gateway_culqi', 50 );

/**
 * Getting discounts, orders from the customer's cart, getting card settings and currency conversion
 */
function cg_action_gateway() {

    global $adsBasket;

    $discount = isset( $_POST['discount'] ) ? sanitize_text_field( $_POST['discount'] ) : '';
    $response = $adsBasket->getBasketOrders( $discount );
    $setting  = ads_ccard_settings();

    if( isset( $response['cur_payment_simbol'] ) && $response['cur_payment_simbol'] != $setting['pay_currency'] ) {

        $response['cur_payment_price']  = ads_price_convert_currents(
            $response['cur_payment_price'],
            $response['cur_payment_simbol'],
            $setting['pay_currency']
        );
        
        $response['cur_payment_simbol'] = $setting['pay_currency'];
    }
    
    wp_send_json( $response );
}
add_action( 'wp_ajax_cg_action_gateway', 'cg_action_gateway', 40 );
add_action( 'wp_ajax_nopriv_cg_action_gateway', 'cg_action_gateway', 40 );
