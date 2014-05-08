<?php

class com_webaccessglobal_veritransIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  private static $_singleton = null;

  /**
   * mode of operation: live or test
   *
   * @var object
   * @static
   */
  protected static $_mode = null;

  static function retrieve($name, $type, $object, $abort = true) {
    $value = CRM_Utils_Array::value($name, $object);
    if ($abort && $value === null) {
      CRM_Core_Error::debug_log_message("Could not find an entry for $name");
      echo "Failure: Missing Parameter - " . $name . "<p>";
      exit();
    }

    if ($value) {
      if (!CRM_Utils_Type::validate($value, $type)) {
        CRM_Core_Error::debug_log_message("Could not find a valid entry for $name");
        echo "Failure: Invalid Parameter<p>";
        exit();
      }
    }

    return $value;
  }

 public function __get($property)
  {
    if (property_exists($this, $property))
    {
      return $this->$property;
    }
  }

  public function __set($property, $value)
  {
    if (property_exists($this, $property))
    {
      $this->$property = $value;
    }

    return $this;
  }

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    parent::__construct();
    CRM_Core_Error::debug_log_message("in constructor");
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   */
  static function &singleton($mode, $component, &$paymentProcessor) {
    if (self::$_singleton === null) {
      self::$_singleton = new com_webaccessglobal_veritransIPN($mode, $paymentProcessor);
    }
    return self::$_singleton;
  }

  /**
   * The function gets called when a new order takes place.
   *
   * @param array  $CivicrmData  contains the CiviCRM related data
   * @param string $component    the CiviCRM component
   * @param array  $TransactionData contains the Merchant related data
   *
   * @return void
   *
   */
  function newOrderNotify($CivicrmData, $component, $TransactionData) {

    $ids = $input = $params = array();
    $input['component'] = strtolower($component);
    $ids['contact'] = self::retrieve('contactID', 'Integer', $CivicrmData, true);
    $ids['contribution'] = self::retrieve('contributionID', 'Integer', $CivicrmData, true);
    CRM_Core_Error::debug('$component',$input['component']);
    if ($input['component'] == "event") {
      $ids['event'] = self::retrieve('eventID', 'Integer', $CivicrmData, true);
      $ids['participant'] = self::retrieve('participantID', 'Integer', $CivicrmData, true);
      $ids['membership'] = NULL;
    }
    else {
      $ids['membership'] = self::retrieve('membershipID', 'Integer', $CivicrmData, FALSE);
      $ids['related_contact'] = self::retrieve('relatedContactID', 'Integer', $CivicrmData, FALSE);
      $ids['onbehalf_dupe_alert'] = self::retrieve('onBehalfDupeAlert', 'Integer', $CivicrmData, FALSE);
      $ids['contributionRecur'] = self::retrieve('contributionRecurID', 'Integer', $CivicrmData, FALSE);
    }

    $ids['contributionRecur'] = $ids['contributionPage'] = null;
    if (!$this->validateData($input, $ids, $objects)) {
      return false;
    }
    CRM_Core_Error::debug('$validateData',"yes");
    // make sure the invoice is valid and matches what we have in the contribution record
    $contribution = & $objects['contribution'];
    $input['invoice'] = $CivicrmData['invoiceID'];
    $input['trxn_id'] = $TransactionData['trxn_id'];
    $input['is_test'] = $CivicrmData['is_test'];
    $input['amount'] = $TransactionData['PurchaseAmount'];
    CRM_Core_Error::debug('$transactionData',$TransactionData['status']);
    $transaction = new CRM_Core_Transaction();
    CRM_Core_Error::debug_var('$TransactionData', $TransactionData['status']);
    switch ($TransactionData['status']) {
      case 'success':
        break;
      default:
	CRM_Core_Error::debug_log_message("here in failed transaction");
        return $this->failed($objects, $transaction);
        break;
    }

    if ($contribution->invoice_id != $input['invoice']) {
      CRM_Core_Error::debug_log_message("Invoice values dont match between database and notification request");
      echo "Failure: Invoice values do not match between database and IPN request<p>";
      return;
    }

    if ($contribution->total_amount != $input['amount']) {
      CRM_Core_Error::debug_log_message("Amount values dont match between database and IPN request");
      echo "Failure: Amount values dont match between database and IPN request. " . $contribution->total_amount . "/" . $input['amount'] . "<p>";
      return;
    }

    // check if contribution is already completed, if so we ignore this ipn
    if ($contribution->contribution_status_id == 1) {
      $transaction->commit();
      CRM_Core_Error::debug_log_message("returning since contribution has already been handled");
      echo "Success: Contribution has already been handled<p>";
      return true;
    }
    else {

      if (CRM_Utils_Array::value('event', $ids)) {
        $contribution->trxn_id = $ids['event'] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['participant'];
      }
      elseif (CRM_Utils_Array::value('membership', $ids)) {
        $contribution->trxn_id = $ids['membership'][0] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['related_contact'] . CRM_Core_DAO::VALUE_SEPARATOR . $ids['onbehalf_dupe_alert'];
      }
    }

    $this->completeTransaction($input, $ids, $objects, $transaction);
    CRM_Core_Error::debug('$complete',"complete");
    return true;
  }

  /**
   * This method is handles the response that will be invoked by the
   * notification or request sent by the payment processor.
   * hex string from paymentexpress is passed to this function as hex string.
   */
  function main($veritransPostData,$processor) {
    $success = false;
    CRM_Core_Error::debug('$veritransPostData',$veritransPostData);
    if(!empty($veritransPostData)) $veritransPostData =  json_decode($veritransPostData);
    $veritrans_orderId = $veritransPostData->orderId;
    $config = CRM_Core_Config::singleton();

    // Getting transaction related data from cache table
    $resp_data = CRM_Core_BAO_Cache::getItem('com.webaccessglobal.veritrans',"Veritrans_orderID_{$veritrans_orderId}", null);
    CRM_Core_BAO_Cache::deleteGroup(NULL,"Veritrans_orderID_{$veritrans_orderId}");
    $component = $resp_data['module'];
    $qfKey = $resp_data['qfKey'];
    CRM_Core_Error::debug('$resp_data',$resp_data);
    $CivicrmData = $ids = $objects = array();
    $CivicrmData['transactionID'] =  $veritrans_orderId;
    $CivicrmData['contributionID'] = $resp_data['contributionID'];
    $CivicrmData['contactID'] = $resp_data['contactID'];
    $CivicrmData['invoiceID'] = $resp_data['invoiceID'];

    if ($component == "event") {
      $CivicrmData['participantID'] = $resp_data['participantID'];
      $CivicrmData['eventID'] = $resp_data['eventID'];
    }
    else if ($component == "contribute") {
      $CivicrmData["membershipID"] = array_key_exists('membershipID', $resp_data) ? $resp_data['membershipID'] : '';
      $CivicrmData["relatedContactID"] = array_key_exists('relatedContactID', $resp_data) ? $resp_data['relatedContactID'] : '';
      $CivicrmData["onbehalf_dupe_alert"] = array_key_exists('onbehalf_dupe_alert', $resp_data) ? $resp_data['onbehalf_dupe_alert'] : '';
    }


    list ($mode, $duplicateTransaction) = self::getContext($CivicrmData,$component);
    CRM_Core_Error::debug('$mode',$mode);
    CRM_Core_Error::debug('$duplicateTransaction',$duplicateTransaction);
    $CivicrmData['is_test'] = $mode;
    $mode = $mode ? 'test' : 'live';

    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($processor['id'], $mode);
    $ipn = self::singleton($mode, $component, $paymentProcessor);

    $TransactionData = array();
    $TransactionData['trxn_id'] = $veritrans_orderId;
    $TransactionData['PurchaseAmount'] = $resp_data['amount'];
    $TransactionData['status'] = $veritransPostData->mStatus;

   if ($duplicateTransaction == 0) {
      $ipn->newOrderNotify($CivicrmData, $component, $TransactionData);
      CRM_Core_Error::debug('$new_order',"new_order");
    }

  }

  function &error($errorCode = null, $errorMessage = null) {
    $e = & CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, null, $errorMessage);
    }
    else {
      $e->push(9001, 0, null, 'Unknowns System Error.');
    }
    return $e;
  }

  /**
   * The function returns the component(Event/Contribute..)and whether it is Test or not
   *
   * @param array   $CivicrmData    contains the name-value pairs of transaction related data
   *
   * @return array context of this call (test, component, payment processor id)
   * @static
   */
  static function getContext($CivicrmData,$component) {

    $isTest = null;
    $contributionID = $CivicrmData['contributionID'];
    $contribution = & new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionID;

    if (!$contribution->find(true)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: $contributionID");
      echo "Failure: Could not find contribution record for $contributionID<p>";
      exit();
    }

    $isTest = $contribution->is_test;
    $duplicateTransaction = 0;
    if ($contribution->contribution_status_id == 1) {
      //contribution already handled. (some processors do two notifications so this could be valid)
      $duplicateTransaction = 1;
    }
    if ($component == 'contribute') {
      if (!$contribution->contribution_page_id) {
        CRM_Core_Error::debug_log_message("Could not find contribution page for contribution record: $contributionID");
        echo "Failure: Could not find contribution page for contribution record: $contributionID<p>";
        exit();
      }
    }
    else {

      $eventID = $CivicrmData['eventID'];
      if (!$eventID) {
        CRM_Core_Error::debug_log_message("Could not find event ID");
        echo "Failure: Could not find eventID<p>";
        exit();
      }

      // we are in event mode
      // make sure event exists and is valid
      //require_once 'CRM/Event/DAO/Event.php';
      $event = & new CRM_Event_DAO_Event();
      $event->id = $eventID;

      if (!$event->find(true)) {
        CRM_Core_Error::debug_log_message("Could not find event: $eventID");
        echo "Failure: Could not find event: $eventID<p>";
        exit();
      }
    }

    return array(
      $isTest,
      $duplicateTransaction
    );
  }


}

