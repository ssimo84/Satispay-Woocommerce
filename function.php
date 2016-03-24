<?php
/*
Plugin Name: WooCommerce Payment Satispay
Plugin URI: http://www.digitalissimoweb.it
Description: Integrate Satispay into Woocommerce site. Send and receive money the smart way! 
Version: 1.0
Author: Digitalissimo
Author URI: http://www.digitalissimoweb.it
Tags: woocommerce, satispay, payment, payment-gateway woocommerce
Requires at least: 4.0.1
Tested up to: 4.3
Stable tag: 4.3
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    

				add_action('plugins_loaded', 'woocommerce_satispay_init', 0);
				
				add_action('wp_ajax_nopriv_satispaybasket',  'satispaybasket');
				add_action('wp_ajax_satispaybasket', 'satispaybasket');
				add_action('wp_ajax_nopriv_satispaycheckstatus',  'satispaycheckstatus');
				add_action('wp_ajax_satispaycheckstatus', 'satispaycheckstatus');
				add_action('wp_ajax_nopriv_redictectSatispay',  'redictectSatispay');
				add_action('wp_ajax_redictectSatispay', 'redictectSatispay');
				
				
				
				function woocommerce_satispay_init(){
				  if(!class_exists('WC_Payment_Gateway')) return;
				
				  class WC_Satispay extends WC_Payment_Gateway{
					public function __construct(){
						
					  //Register Styles
						add_action( 'wp_enqueue_scripts', array( &$this, 'register_styles' ) );
						
						$plugin_dir = basename(dirname(__FILE__));
						load_plugin_textdomain( 'satispay', false, $plugin_dir . '/i18n/' );	
						
						
						$this -> id = 'satispay';
						$this -> medthod_title = 'Satispay';
						$this -> has_fields = false;
						
						$this->init_form_fields();
						$this->init_settings();
						$this->versione = 'v1';
						$this->test =  $this->settings['test'];
						$this->enabled = $this -> settings['enabled'];
						$this->title = $this -> settings['title'];
						$this->description = $this -> settings['description'];
						$this->merchant_id = $this -> settings['merchant_id'];
						$this->nomenegozio = $this -> settings['nomenegozio'];
						// $this -> salt = $this -> settings['salt'];
						//$this -> redirect_page_id = $this -> settings['redirect_page_id'];
						
						if ($this->test=="no")  $this -> liveurl = 'https://authservices.satispay.com/online/' . $this->versione . "/";
						else $this -> liveurl = 'https://staging.authservices.satispay.com/online/' . $this->versione . "/";
						
						$this->urlpromo = 	"https://satispay.com/promo/" . $this->nomenegozio;
						
						//add_action('init', array(&$this, 'check_satispay_response'));
						
						if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
							 } else {
								add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
							}
						add_action('woocommerce_receipt_satispay', array(&$this, 'receipt_page'));
				   }
				   
				 
				   
				   
					public function get_icon() {
				
						$icon_html = '<img src="' .   plugins_url( 'assets/img/button-grey.png', __FILE__ )  . '"  alt="' . esc_attr( $this->get_title() ) . '" />';
						
						$icon_html .= sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WISatispay\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What Satispay?', 'satispay' ) . '">' . esc_attr__( 'What Satispay?', 'woocommerce' ) . '</a>', 'https://www.satispay.com/' );
						
						return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
					}
					
					function init_form_fields(){
				
					   $this -> form_fields = array(
								'enabled' => array(
									'title' => __('Enable/Disable', 'satispay'),
									'type' => 'checkbox',
									'label' => __('Enable Satispay Payment Module.', 'satispay'),
									'default' => 'no'),
									
								'test' => array(
									'title' => __('For developer?', 'satispay'),
									'type' => 'checkbox',
									'label' => __('Enable Satispay for Test', 'satispay'),
									'default' => 'no'),	
								
								
								'merchant_id' => array(
									'title' => __('Token Merchant ID', 'satispay'),
									'type' => 'text',
									'description' => __('Request your security token to satispay for your online shop', 'satispay'),
									 'default' => ''),
									
								'title' => array(
									'title' => __('Title:', 'satispay'),
									'type'=> 'text',
									'description' => __('This controls the title which the user sees during checkout.', 'satispay'),
									'default' => __('Satispay', 'satispay')),
								'description' => array(
									'title' => __('Description:', 'satispay'),
									'type' => 'textarea',
									'description' => __('This controls the description which the user sees during checkout.', 'satispay'),
									'default' => __('Send and receive money the smart way!', 'satispay')),
								
								
								'nomenegozio' => array(
									'title' => __('Nome negozio', 'satispay'),
									'type' => 'text',
									'description' => __('Insert the name of the store to the Welcome Bonus', 'satispay')),
								
							
								
								
								
							);
					}
				
				
				
					   public function admin_options(){
						echo '<h3>'.__('Satispay Payment Gateway', 'satispay').'</h3>';
						echo '<p>'.__('Satispay is a mobile payment').'</p>';
						echo '<table class="form-table">';
						// Generate the HTML For the settings form.
						$this -> generate_settings_html();
						echo '</table>';
				
					}
				
					/**
					 *  There are no payment fields for payu, but we want to show the description if set.
					 **/
					function payment_fields(){
						if($this -> description) echo wpautop(wptexturize($this -> description));
					}
					/**
					 * Receipt Page
					 **/
					function receipt_page($order){
						
						echo $this -> generate_payu_form($order);
					}
					/**
					 * Generate payu button link
					 **/
					public function generate_payu_form($order_id){
				
					   global $woocommerce;
					   
					   
					 
					   
						$order = new WC_Order( $order_id );
						$txnid = $order_id.'_'.date("ymds");
				
						$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
				
						//$productinfo = "Acquisto da  "  .  strtoupper(get_bloginfo( 'name' )) .  " Ordine n. $order_id";
						$productinfo = sprintf(__("Buying from %d - Order no. %o", 'satispay'),strtoupper(get_bloginfo( 'name' )),$order_id );
						
						/*$str = "$this->merchant_id|$txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email|||||||||||$this->salt";
						
						$hash = hash('sha512', $str);*/
				
				
						$amount = $order -> order_total;
						
						list ($integer,$decimal) = explode (wc_get_price_decimal_separator(),$amount);
						
						$integer_sep =  explode (wc_get_price_thousand_separator(),$integer);
						$integer = $integer_sep[0] .  $integer_sep[1];
							
						$payu_args = array(
						 // 'key' => $this -> merchant_id,
						  'txnid' => $txnid,
						  'amount' => $order -> order_total,
						  'getInteger' => $integer . "" . $decimal,
						  'productinfo' => $productinfo,
						  'firstname' => $order -> billing_first_name,
						  'lastname' => $order -> billing_last_name,
						  'address1' => $order -> billing_address_1,
						  'address2' => $order -> billing_address_2,
						  'city' => $order -> billing_city,
						  'state' => $order -> billing_state,
						  'country' => $order -> billing_country,
						  'zipcode' => $order -> billing_zip,
						  'email' => $order -> billing_email,
						  'phone' => $order -> billing_phone,
						  'hash' => $hash,
						  'pg' => 'NB'
						  );
				
						$payu_args_array = array();
						foreach($payu_args as $key => $value){
						  $payu_args_array[] = "<input type='hidden' id='$key'  name='$key' value='$value'/>";
						}
						
						
						//$form= '<form action="'.$this->liveurl.'" method="post" id="satispay_form">' . implode('', $payu_args_array);
							$form= '<form   method="post" id="satispay_form"  name="form" >' . implode('', $payu_args_array);
							
							
							
							
							
							 $form .= '<div class="satispay_class">';
							 
							 $form .= '';
							 
							 $form .=  '
							 
							 
							 <DIV CLASS="loading_satispay"></DIV>
							 
							 <div class="form_satispay"><p> ' . __("To confirm your order, please enter", "satispay") . " <b>" . __("the phone number with which you joined satispay", "satispay") . '</b>: '  .  __("You receive a request for payment. Open the App and confirm the payment.","satispay") .  ' <br/><small>' . __("If you are not registered","satispay") . ', <a class="link-red" href="' . $this->urlpromo  . '"  target="_blank">' . __("sign up now","satispay")  . '.</a></small></p>';
					
						$form .=  '
						
							<div class="telefono alt error_internal" id="error_internal_0"><div class="message_satispay">' . __("You need to enter your phone number","satispay") . '</div></div>
						
							<input class="number_phone_satispay"  type="tel" value="+39' . $order -> billing_phone . '">
							<input class="number_phone_satispay_full" type="hidden" name="number_phone_satispay_full">
							<input type="button" class="button-alt btn-submit_satispay"/> 
							
							</div>
							</form>
							
							<!-Inizio Label errori , warning e messaggi-->
							
							<div class="light_satispay alt" id="error_external">
								<div class="message_satispay">' . __("Internal error, please try again later","satispay") . '</div>
							</div>
							
							<div class="light_satispay light_satispay_yellow" id="error_external_49" >
								<div class="message_satispay"> 
									<h3><strong>' . __("The number","satispay") . ' <span class="numero">+39</span> ' .  __("is not registered with the service","satispay") . '</strong></h3>
				
									<p>' . __("To send money you must have a Satispay account","satispay") .  '<br />
									</p>
										<div class="text-muted vertical" >
										  
											<a target="new" class="btn_satispay" href="https://www.satispay.com/promo/' . $this -> nomenegozio . '">' .  __("Sign up for free to Satispay","satispay") . '</a>
										</div>
									</div>
							</div>    
								
								
							
								
							<div class="light_satispay light_satispay_blue" id="waiting_satispay">
								<div class="message_satispay"> 
									<h3 class="display-inline"><strong>'   . __("Your request has been sent","satispay") . '</strong></h3>
					
									<p>'   . __("We have sent your request to the number","satispay") . ' <strong class="numero"></strong>
										<br>'
										.  __("to conclude your payment","satispay") . '<strong>'
										.  __("open the App Satispay and accepts the request","satispay") . '.</strong><br>
									</p>
								</div> 
							</div>
								
						
							
							<div class=" light_satispay light_satispay_red" id="satispay_FAILURE">	
								<div class="message_satispay"> 
									'
										.  __("The payment has been annulled","satispay") . '<br/>
									'
										.  __("Please try again to make the payment","satispay") . '
								</div>
							</div>
							
							
							<div class=" light_satispay light_satispay_green" id="satispay_SUCCESS">	
								<div class="message_satispay"> 
									'
										.  __("The transaction is successful","satispay") . '<br/>
									'
										.  __("A receipt has been sent to your email account", "satispay") .   '<br/>
								</div>
							</div>
						
								
								
						
						</div>
					
					</div>
						
							
							
							<a class="button cancel"  href="'.$order->get_cancel_order_url().'">'.__('Returns to cart', 'satispay').'</a>
							</form></div>
							<script type="text/javascript">
							jQuery(function(){
									
									
								
									jQuery(".number_phone_satispay").intlTelInput({
										defaultCountry: ["it"],
										preferredCountries: ["it"],
										 utilsScript: "' . plugins_url( '/lib/intl-tel-input/js/utils.js', __FILE__ ) . '"
									});
										
									
									jQuery(".btn-submit_satispay").click(function() {
										callsatispay();
									});
									
									jQuery(".satispay_class").keypress(function(e) {
										
  										if (e.which == 13) {
											e.preventDefault();
											callsatispay();
										}
									});
									
								
										
							});
							
							
							function callsatispay(){
							
										jQuery(".error_internal").hide();
										jQuery(".light_satispay").hide();
										
										
										var numberfull = jQuery(".number_phone_satispay").intlTelInput("getNumber");
										var description = jQuery("#productinfo").val();
										var txnid = jQuery("#txnid").val();
										var unit = jQuery("#getInteger").val();
										var ajaxurl = "' .  admin_url( 'admin-ajax.php' ) . '";
										
										jQuery.ajax({
											type: "POST",
											url: ajaxurl,
											dataType : "json",
											method : "POST",
											data: {
												action:"satispaybasket",
												verb:"post",
												numberfull: numberfull,
												description: description,
												txnid: txnid,
												unit: unit
											},
											beforeSend: function(response) {jQuery(".loading_satispay").show(); },
											success: function(response) {
												//console.log(response);
												jQuery(".loading_satispay").hide();
												//C\'è un errore interno, prima dell\'API Satispay
												//console.log(response);
												if ((isSet(response.type)) && (response.type=="internal")){
													//console.log(response.error);
													switch(response.error){
														case 0:
															console.log(response.error);
															jQuery("#error_internal_0").show();
														break;
														default: console.log("no")
													}
												}
												
												
												if ((isSet(response.type)) && (response.type=="external")){
														switch(response.error){
															case 52:
																jQuery("#error_external").html("' . __("Internal error, please try again later","satispay") . '");
																jQuery("#error_external").show();
															
															
															case 39:
																jQuery("#error_external").html("' . __("The phone number is not formatted correctly","satispay") . '");
																jQuery("#error_external").show();
																
															case 49:
																jQuery("#error_external_49").show();
																jQuery("#error_external_49 .numero").html(numberfull);
															
															
															default: console.log("no")
														}	
													}
												
												//Tutto ok
												if (isSet(response.uuid)){
													 if (response.status == "REQUIRED") {
														jQuery("#waiting_satispay").show();
														jQuery("#waiting_satispay .numero").html(numberfull);
														paymentStatus(response.uuid);
													 }
													
												}
												
											},
											error: function(response) {
												jQuery("#error_external").html("' . __("Internal error, please try again later","satispay") . '");
												jQuery("#error_external").show();
											}
										
										});
								
							}
							
							function paymentStatus(uuid){
								var ajaxurl = "' .  admin_url( 'admin-ajax.php' ) . '";
										
								jQuery.ajax({
									type: "POST",
									url: ajaxurl,
									dataType : "json",
									method : "POST",
									data: {
										action:"satispaycheckstatus",
										verb:"get",
										uuid:uuid,
										orderid:"' . $order_id . '"
									},
									success: function(response) {
										//console.log(response);
										if (response.status == "REQUIRED") {
											setTimeout(paymentStatus(uuid),8000);
										}
										
										if (response.status != "REQUIRED") {
											jQuery("#satispay_"+response.status).show();
											jQuery("#waiting_satispay").hide();
											//jQuery(".form_satispay").hide();
											jQuery(".cancel").hide();
											
											 //setTimeout(function () {
											jQuery.ajax({
												type: "POST",
												url: ajaxurl,
												method : "POST",
												data: {
													action:"redictectSatispay",
													verb:"get",
													uuid:uuid,
													orderid:"' . $order_id . '",
													status: response.status 
												},
												success: function(response) {
													console.log("Redirect:" + response);
													setTimeout(jQuery(location).attr("href", response),5000);
												}
											
											});
														
											//}, 5000);
											
										}
										
									},
									error: function(response) {
										jQuery("#error_external").html("' . __("Internal error, please try again later","satispay") . '");
										jQuery("#error_external").show();
									}
								
								});
								
								
							
							}
							
							function isSet(iVal){
									return (iVal!=="" && iVal!=null && iVal!==undefined && typeof(iVal) != "undefined") ? true: false;
								}
							</script>
							';
				
							
							return $form;
				
				
					}
					/**
					 * Process the payment and return the result
					 **/
					function process_payment($order_id){
						global $woocommerce;
						$order = new WC_Order( $order_id );
						return array('result' => 'success', 'redirect' => add_query_arg('order',
							$order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
						);
					}
				
					
				
					function showMessage($content){
							return '<div class="box '.$this -> msg['class'].'-box">'.$this -> msg['message'].'</div>'.$content;
					}
					
					
					 // get all pages
					function get_pages($title = false, $indent = true) {
						$wp_pages = get_pages('sort_column=menu_order');
						$page_list = array();
						if ($title) $page_list[] = $title;
						foreach ($wp_pages as $page) {
							$prefix = '';
							// show indented child pages?
							if ($indent) {
								$has_parent = $page->post_parent;
								while($has_parent) {
									$prefix .=  ' - ';
									$next_page = get_page($has_parent);
									$has_parent = $next_page->post_parent;
								}
							}
							// add to page list array array
							$page_list[$page->ID] = $prefix . $page->post_title;
						}
						return $page_list;
					}
					
					
					  function register_styles() {
					   
						
						
						wp_register_style( 'satispay_css', plugins_url( '/assets/css/style.css', __FILE__ ), array(), time(), 'all' );
						wp_enqueue_style( 'satispay_css' );
						
						 wp_register_style( 'satispay_inputnumber_css', plugins_url( '/lib/intl-tel-input/css/intlTelInput.css', __FILE__ ), array(), time(), 'all' );
						 
						  wp_enqueue_style( 'satispay_inputnumber_css' );
						  
						  
						  wp_register_script( 'satispay_inputnumber_js',plugins_url( '/lib/intl-tel-input/js/intlTelInput.min.js', __FILE__ ), array( 'jquery' ), time() );
						wp_enqueue_script( 'satispay_inputnumber_js' );
						
						
						 
				
				
					
				
				
					}
				
					
					
				
					
					
				}
				
				
				}
				
				   /**
					 * Add the Gateway to WooCommerce
					 **/
					function woocommerce_add_mrova_payu_gateway($methods) {
						$methods[] = 'WC_Satispay';
						return $methods;
					}
				
					add_filter('woocommerce_payment_gateways', 'woocommerce_add_mrova_payu_gateway' );
				
				
				
				
				
				  
				  
				  
				  
				   /**
					 * Check for valid payu server callback
					 **/
				   function satispaybasket(){
					   
						//header('Content-Type: application/json');
					   
						global $woocommerce;
						$satispay = new WC_Satispay();
						$number = $_POST['numberfull'];
						$description = $_POST['description'];
						$unit = $_POST['unit'];
						$txtnid = $_POST['txtnid'];
						$merchandid = $satispay->merchant_id;
						
						
						if(isset($number) && ($number!="")){
							
							
							//1-CONTROLLO SE ESISTE GIA' L'UTENTE    -   //USERS METHOD GET
							//echo  $this->merchant_id;
							$httpHeader = array(
								"Content-Type: application/json",
								//"Idempotency-Key: " . $txtnid ,
								"Authorization:Bearer " . $merchandid . "",
							);
							
							//echo $satispay->liveurl . "users";
							$users_get = satispay_curl($satispay->liveurl . "users","GET",array(),$httpHeader);
							
							$lista_user = $users_get->list;
							$found = $users_get->found;
							$uuid= 0;
							
							
							//controllo IN LISTA USER
							if ($found > 0){
								$found = 0;
								foreach($lista_user  as $item){
									
									if($item->phone_number == $number){
										$found = 1;
										$uuid = $item->uuid;
									}
								}
							}
							
							
							
							
							//2-SE NON ESISTE CREO USER
							if ($found == 0 ){
								
								$param = array("phone_number"=>$number);
								$bodyparam = json_encode($param); 
								$httpHeader = array(
									"Content-Type: application/json",
									"Idempotency-Key: " . $txtnid ,
									"Authorization:Bearer " . $merchandid ,
									"Content-Length: " . strlen($bodyparam) 
								);
								
								
								$return = satispay_curl($satispay->liveurl . "users","POST",$bodyparam ,$httpHeader);
								
								if (isset($return->code)){
									switch ($return->code){
									
										case "52":
											$msg_error=__("Internal error, shop does not exist","satispay");
										case "36":
											$msg_error=__("you need to enter your phone number","satispay");
										case "39":
											$msg_error=__("The phone number is not formatted correctly","satispay");
										case "49":
											$msg_error=__("The phone number is not registered with the service","satispay");	 
										
									}
									
									$return = array("type"=>"external","error"=>$return->code, "message"=>$msg_error);
									
									//echo json_encode($msgerror);
									//wp_die();
								} else {
									
									
									$uuid = $return->uuid;
									
									
									
								}
							}
							
						
							
							
							//3-RECUPERO L'ID E PROSEGUO
							if ($uuid!="0"){
							
								$param = array(   
										"description"=>$description,
										"currency"=>"EUR",
										"amount"=>$unit,
										"user_uuid"=>$uuid,
										"required_success_email"=>"true",
										"expire_in"=>20
										);
					
								
								
								$bodyparam = json_encode($param);
								
								//$bodyparam = '';
							
								$httpHeader = array(
									"Content-Type: application/json",
									//"Idempotency-Key: " . $txtnid ,
									"Authorization:Bearer " . $merchandid ,
									"Content-Length: " . strlen($bodyparam) 
								);
					
								$return = satispay_curl($satispay->liveurl . "charges","POST",$bodyparam ,$httpHeader);
							
							}
							
						} else {
								
							$return = array("type"=>"internal","error"=>0, "message"=>__("You need to enter your phone number","satispay"));
							
								
						}
						
						
						echo json_encode ($return);
						wp_die();
				
					}
				  
				  
					function satispaycheckstatus(){
						
						global $woocommerce;
						$satispay = new WC_Satispay();
						$uuid = $_POST['uuid'];
						$orderid = $_POST['orderid'];
						$merchandid = $satispay->merchant_id;
						
						$httpHeader = array(
							"Content-Type: application/json",
							//"Idempotency-Key: " . $txtnid ,
							"Authorization:Bearer " . $merchandid . "",
						);
							
						
						$order = new WC_Order($orderid);
						$return = satispay_curl($satispay->liveurl . "charges/" .$uuid,"GET", array() ,$httpHeader);
						$check = json_encode ($return);
						//$order->update_status( 'failed');
						//Se è andato tutto bene devo salvare nel db la transizione
						if ($return->status=="SUCCESS" ){
							$msg =__("Payment successfully completed with Satispay","satispay") . ":" . $return->user_short_name . " " . $return->expire_date ;
							$order->update_status( 'processing');
							$order->add_order_note($msg);
							$woocommerce->cart->empty_cart();
							
							//wp_redirect(home_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key );
							
							
						}
						
						//Se è andata male devo salvare il pagamento annullato
						if ($return->status=="FAILURE" ){
							$order->update_status( 'failed');
							$msg =  __("Payment Failed","satispay")  . ":" . $return->status_details . " "  . $return->user_short_name . " " .$return->expire_date ;
							//$woocommerce->cart->empty_cart();
							$order->add_order_note($msg);
							
							
							//wp_redirect(get_site_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key );
							
							
						}
						
						
						echo $check ;
						wp_die();
						
					}
				  
				   function satispay_curl($url,$method,$param,$httpHeader){
						
						$ch = curl_init();
						
						/*
						
						$connect_timeout = 5; //sec
						
						$base_time_limit = (int) ini_get('max_execution_time');
						if ($base_time_limit < 0) {
						$base_time_limit = 0;
						}
						$time_limit = $base_time_limit - $connect_timeout - 2;
						if ($time_limit <= 0) {
						$time_limit = 20; //default
						}
						*/
						//curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
						//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
						//curl_setopt($ch, CURLOPT_TIMEOUT, $time_limit);
						curl_setopt($ch, CURLOPT_URL, $url);
					
						curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
						if (strtoupper($method)=="POST"){
							curl_setopt($ch, CURLOPT_POST, 1);
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");   
							curl_setopt($ch, CURLOPT_POSTFIELDS, $param );
						}
						//curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
						//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						
						$result = curl_exec($ch);
						
						$info = curl_getinfo($ch);
						if (!isset($info['http_code'])) {
						$info['http_code'] = '';
						}
						
						$curl_errno = curl_errno($ch);
						$curl_error = curl_error($ch);
						
						if (curl_errno($ch)) {
							$return= array(
								'http_code' => $info['http_code'],
								//'info' => $info,
								'status' => 'ERROR1',
								'errno' => $curl_errno,
								'error' => $curl_error,
								'result' => NULL
							);
						} else {
							$return = json_decode($result); 
						}
						//var_dump($info);
						//echo $url;
						//wp_die();
						
						curl_close($ch);
						return $return;
					
					}
				
				
				
				
				
				
					function redictectSatispay(){
						global $woocommerce;
						$uuid = $_POST['uuid'];
						$orderid = $_POST['orderid'];
						$order = new WC_Order($orderid);
						$status = $_POST['status'];
						//if ($status=="SUCCESS")
						//wp_redirect(home_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key );
						//echo home_url().'/checkout/order-received/'.$order->id.'/?key='.$order->order_key ;
						echo $order->get_checkout_order_received_url();
						wp_die();
					}



}















