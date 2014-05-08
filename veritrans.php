<?php

require_once 'veritrans.civix.php';
require_once 'veritranspayment.php';
/**
 * Implementation of hook_civicrm_config
 */
function veritrans_civicrm_config(&$config) {
  _veritrans_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function veritrans_civicrm_xmlMenu(&$files) {
  _veritrans_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function veritrans_civicrm_install() {

 CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_payment_processor_type` (`name`, `title`, `description`, `is_active`, `is_default`, `user_name_label`, `password_label`, `signature_label`, `subject_label`, `class_name`, `url_site_default`, `url_api_default`, `url_recur_default`, `url_button_default`, `url_site_test_default`, `url_api_test_default`, `url_recur_test_default`, `url_button_test_default`, `billing_mode`, `is_recur`, `payment_type`) VALUES
('Veritrans', 'Veritrans gateway', 'Veritrans Payment Processor', 1, NULL, 'Merchant ID', NULL, 'Merchant Hash Key', NULL, 'com.webaccessglobal.veritrans', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, 1)");

  return _veritrans_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function veritrans_civicrm_uninstall() {

  CRM_Core_DAO::executeQuery("DELETE  FROM civicrm_payment_processor where name = 'Veritrans'");
  CRM_Core_DAO::executeQuery("DELETE  FROM civicrm_payment_processor_type where name = 'Veritrans'");
  $affectedRows = mysql_affected_rows();

  if($affectedRows)
    CRM_Core_Session::setStatus("Veritrans Payment Processor Message:
    <br />Entries for Veritrans Payment Processor are now Deleted!
    <br />");



  return _veritrans_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function veritrans_civicrm_enable() {
  CRM_Core_DAO::executeQuery("UPDATE civicrm_payment_processor pp
RIGHT JOIN  civicrm_payment_processor_type ppt ON ppt.id = pp.payment_processor_type_id
SET pp.is_active = 1
WHERE ppt.name = 'Veritrans'");

  $affectedRows = mysql_affected_rows();

  if($affectedRows)
    CRM_Core_Session::setStatus("Veritrans Payment Processor Message:
    <br />Entries for Veritrans  Payment Processor are now Enabled!
    <br />");

  return _veritrans_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function veritrans_civicrm_disable() {
  CRM_Core_DAO::executeQuery("UPDATE civicrm_payment_processor pp
RIGHT JOIN  civicrm_payment_processor_type ppt ON ppt.id = pp.payment_processor_type_id
SET pp.is_active = 0
WHERE ppt.name = 'Veritrans'");
  $affectedRows = mysql_affected_rows();

  if($affectedRows)
    CRM_Core_Session::setStatus("Veritrans Payment Processor Message:
    <br />Entries for Veritrans Payment Processor are now Disabled!
    <br />");
  return _veritrans_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function veritrans_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _veritrans_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function veritrans_civicrm_managed(&$entities) {
  return _veritrans_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function veritrans_civicrm_caseTypes(&$caseTypes) {
  _veritrans_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function veritrans_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _veritrans_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
