<?php
class CRM_Veritrans_Page_Message extends CRM_Core_Page {
  function browse(){
    $getData = $_GET;
    $registerURL = null;
    $baseURL = null;

    $event_id = explode("?",$getData['rid']);
    $e_id = $event_id[0];

    if( !empty($getData['resp_code']) ) {
      $displayMsg = null;
      ($getData['component'] == "event") ? $baseURL = 'civicrm/event/register' :  $baseURL = 'civicrm/contribute/register';
      $query = "?reset=1&id=".$e_id;

      if($getData['resp_code'] == "c") $displayMsg = "Registration could not be completed due to transaction failure";
      elseif($getData['resp_code'] == "f") {
        $displayMsg = "Registration could not be completed due to cancellation";
        $transaction_id = $getData['trxn_id'];
        $this->assign( 'transaction_id', $transaction_id );
      }
    } else {
      $displayMsg = ts("Unknown System Error");
    }
    //$finalURL = CRM_Utils_System::url($baseURL, $query, false, null, false);
    $finalURL = CRM_Utils_System::baseCMSURL().$baseURL.$query;
    $this->assign( 'dispalyMessage', $displayMsg );
    $this->assign( 'registerURL', $finalURL );

  }
  function run() {
    $this->browse();
    return parent::run();
  }
}

