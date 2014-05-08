<?php

class com_webaccessglobal_veritrans extends CRM_Core_Payment {

  CONST CHARSET = 'UFT-8';

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  protected static $_mode = null;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $config = CRM_Core_Config::singleton();
    $this->templateDir = $config->extensionsDir . '/com.webaccessglobal.veritrans/';

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Veritrans');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (!isset(self::$_singleton[$processorName]) || self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new com_webaccessglobal_veritrans($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /*
   * This function  sends request and receives response from
   * the processor. It is the main function for processing on-server
   * credit card transactions
   */

  function doDirectPayment(&$params) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }


  function doTransferCheckout(&$params, $component = 'contribute') {    
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }
    //echo "<pre>";print_r($params);exit;	
    if(!empty($params['event_title']))	$lines = explode("\n", wordwrap($params['event_title'], 20));
    elseif(!empty($params['item_name'])) $lines = explode("\n", wordwrap($params['item_name'], 20));  		 
    
    if(!empty($lines)) $desc = $this->clean_url($lines[0]);
		  else $desc = "amount"; 
 	    
    // START --- civicrm related data
    $arr_cache_veritrans = array();
    $arr_cache_veritrans['qfKey'] = $params['qfKey'];
    $arr_cache_veritrans['contactID'] = $params['contactID'];
    $arr_cache_veritrans['contributionID'] = $params['contributionID'];
    $arr_cache_veritrans['component'] = $component;
    $arr_cache_veritrans['amount'] = $params['amount'];
    $arr_cache_veritrans['module'] = $component;

    if ($component == 'event') {
      $arr_cache_veritrans['eventID'] = $params['eventID'];
      $arr_cache_veritrans['participantID'] =$params['participantID'];
    }
    else {
      $membershipID = CRM_Utils_Array::value('membershipID', $params);
      if ($membershipID) {
        $arr_cache_veritrans['membershipID'] = $membershipID;
      }
      $relatedContactID = CRM_Utils_Array::value('related_contact', $params);
      if ($relatedContactID) {
        $arr_cache_veritrans['relatedContactID'] = $relatedContactID;
        $onBehalfDupeAlert = CRM_Utils_Array::value('onbehalf_dupe_alert', $params);
        if ($onBehalfDupeAlert) {
          $arr_cache_veritrans['onbehalf_dupe_alert'] = $onBehalfDupeAlert;
        }
      }
    }
    if (!empty($params['invoiceID']))  {
      $arr_cache_veritrans['invoiceID'] = $params['invoiceID'];
    }
    // END --- civicrm related data

    // START --- Veritrans Payment Gateway data
    $Veritrans_orderID = substr($params['invoiceID'],-20);
    //echo $Veritrans_orderID;
    //$v_amount = explode(",",$params['amount']);
    $v_amount = $params['amount'];
    //$v_amount = str_replace(".","",$v_amount[0]);

    $Veritrans_amount = round($v_amount);
	
    require_once 'veritrans_inc.php';
    $veritrans = new Veritrans;

    $ret_url = CRM_Utils_System::baseCMSURL() . "civicrm/payment/ipn?processor_name=Veritrans";
    if ($component == 'contribute') {
      $success_url = $ret_url."&md=contribute&qfKey=" . $params['qfKey'] . '&inId=' . $Veritrans_orderID. '&tr_status=success';
      $error_url = $ret_url."&md=contribute&qfKey=" . $params['qfKey'] . '&inId=' . $Veritrans_orderID. '&tr_status=failure';

      //$cancel_url = CRM_Utils_System::baseCMSURL()."civicrm/contribute/transact?_qf_Main_display=true&cancel=1&qfKey=".$params['qfKey'];
    }
    else if ($component == 'event') {
      $success_url = $ret_url."&md=event&qfKey=".$params['qfKey'] . '&inId=' . $Veritrans_orderID;
      $error_url = $ret_url."&md=event&qfKey=" . $params['qfKey'] . '&inId=' . $Veritrans_orderID. '&eventId=' .$params['eventID'];
      $cancel_url = CRM_Utils_System::baseCMSURL()."civicrm/payment/veritrans/message?lcMessages=bg_BG&component=event&resp_code=c&rid=".$params['eventID'];
      //$cancel_url = CRM_Utils_System::baseCMSURL()."civicrm/contribute/transact?_qf_Main_display=true&cancel=1&reset=1&qfKe y=".$params['qfKey'];
    }

    $veritrans->settlement_type = '01'; /// default value set
    $signature = $this->_paymentProcessor['user_name'];
    $veritrans->merchant_id = $signature;
    $veritrans->merchant_hash_key = $this->_paymentProcessor['signature'];

    $veritrans->finish_payment_return_url = urlencode($success_url);
    $veritrans->unfinish_payment_return_url = urlencode($cancel_url);
    $veritrans->error_payment_return_url = urlencode($error_url);

    $veritrans->order_id = $Veritrans_orderID;
    $veritrans->session_id = $Veritrans_orderID;
    $veritrans->gross_amount = $Veritrans_amount;

    $veritrans->card_capture_flag = '1';
    $veritrans->billing_address_different_with_shipping_address = 1;
    $veritrans->required_shipping_address = 0;

     /// Amount must have to pass in commodity array format
    $commodities =  array (
                      array("COMMODITY_PRICE" => $Veritrans_amount,
                      "COMMODITY_QTY" => '1',
                      "COMMODITY_ID" => 'amount',
                      "COMMODITY_NAME1" => $desc,
                      "COMMODITY_NAME2" => $desc
			               )
                   ); /// veritrans amount
    $veritrans->commodity = $commodities;
    $key = $veritrans->get_keys(); /// return the keys of token browser and token merchant
   // echo "<pre>";print_r($veritrans);print_r($key);exit;	
    if(!empty($key['token_merchant'])){
      $arr_cache_veritrans['token_merchant'] = $key['token_merchant'];
    } /// for storing it into cache table

	/* $data will be assigned to veritrans.tpl and form will be posted to transaction URL */
    $data = array('merchant_id' => $signature,
      'order_id' => $veritrans->order_id,
      'token_browser' => $key['token_browser'],
      'amount' => $params['amount'],
      'redirect_url'=>$veritrans::PAYMENT_REDIRECT_URL
    );

    // END --- Veritrans Payment Gateway data

    // Insert current user's last selected form preferences into cache table
     CRM_Core_BAO_Cache::setItem($arr_cache_veritrans, 'com.webaccessglobal.veritrans',"Veritrans_orderID_{$Veritrans_orderID}", null);

    /*  important because without storing session objects,
     *  civicrm wouldnt know if the confirm page ever submitted as we are using exit at the end
     *  and it will never redirect to the thank you page, rather keeps redirecting to the confirmation page.
    */

    require_once 'CRM/Core/Session.php';
    CRM_Core_Session::storeSessionObjects();

    $template = CRM_Core_Smarty::singleton();
    $tpl_file = $this->templateDir.'veritrans.tpl';
    $template->assign('data', $data);
    $tpl = $template->fetch($tpl_file);
    print $tpl;
    exit;
  }

  function &error($error = NULL) {
    $e = CRM_Core_Error::singleton();
    if (is_object($error)) {
      $e->push($error->getResponseCode(), 0, NULL, $error->getMessage()
      );
    }
    elseif (is_string($error)) {
      $e->push(9002, 0, NULL, $error
      );
    }
    else {
      $e->push(9001, 0, NULL, "Unknown System Error.");
    }
    return $e;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();
    if (empty($this->_paymentProcessor['signature'])) {
      $error[] = ts('PS Store ID is not set in the Administer CiviCRM & raquo;
          System Settings & raquo;
          Payment Processors.');
    }

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('HPP KEY is not set in the Administer CiviCRM & raquo;
          System Settings & raquo;
          Payment Processors.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  function get_redirect($_GET){
    require_once 'CRM/Utils/Array.php';
    $qfKey = CRM_Utils_Array::value('qfKey', $_GET);
    $module = CRM_Utils_Array::value('md', $_GET);
    $trxn_id1 = CRM_Utils_Array::value('inId', $_GET);
    $a_trxn_id = explode("?",$trxn_id1);
    $trxn_id =	$a_trxn_id[0];
	
    switch ($module) {
      case 'contribute':
	      if ($_GET['status'] == 'failure') {
          $url = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Confirm_display=true&qfKey={$qfKey}", FALSE, NULL, FALSE);
	      }
	      else {
          $url = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_ThankYou_display=1&qfKey={$qfKey}", FALSE, NULL, FALSE);
	      }
	      break;

      case 'event':
	      if ($_GET['status'] == 'failure') {
	      $event_id = CRM_Utils_Array::value('eventId', $_GET);		      	
              $url = CRM_Utils_System::url('civicrm/payment/veritrans/message', "resp_code=f&component=event&trxn_id={$trxn_id}&rid={$event_id}&lcMessages=bg_BG", FALSE, NULL, FALSE);
        }
	      else { // error code
	          $url = CRM_Utils_System::url('civicrm/event/register', "_qf_ThankYou_display=1&qfKey={$qfKey}&trxnId={$trxn_id}", FALSE, NULL, FALSE);
	      }
	      break;

      default:
	     require_once 'CRM/Core/Error.php';
	     CRM_Core_Error::debug_log_message("Could not get module name from request url");
	     echo "Could not get module name from request url\r\n";
    }
    CRM_Utils_System::redirect($url);
  }


  /**
   * Handle return response from payment processor
   */

  public function handlePaymentNotification() {

   if(isset($_GET['qfKey']) && !empty($_GET['qfKey'])){
     $this->get_redirect($_GET);
   }
   else{
     require_once 'veritransipn.php';
     	
     $veritransIPN = new com_webaccessglobal_veritransIPN($this->_mode, $this->_paymentProcessor);
     $response_data = file_get_contents('php://input');
     CRM_Core_Error::debug('$response_data',$response_data);
     if(!empty($response_data))	$veritransIPN->main($response_data,$this->_paymentProcessor);
     else{
       CRM_Core_Error::debug_log_message("Response not received");
       return;
     }
   }
 }

	function clean_url($string)  
	{  
		// Replace other special chars  
		$specialCharacters = array(  
		'#' => '',  
		'’' => '', 
		'`' => '', 
		'\'' => '', 
		'$' => '',  
		'%' => '',  
		'&' => '',  
		'@' => '',  
		'.' => '',  
		'€' => '',  
		'+' => '',  
		'=' => '',  
		'§' => '',  
		'\\' => '',  
		'/' => '',
		'`' => '',
		'•' => '',
		':' => ''
		);

		while (list($character, $replacement) = each($specialCharacters)) {  
		$string = str_replace($character, '', $string);  
		}  
		return $string;  	
	}

}






