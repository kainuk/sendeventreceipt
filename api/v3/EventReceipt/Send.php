<?php
use CRM_Sendeventreceipt_ExtensionUtil as E;

/**
 * EventReceipt.Send API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_event_receipt_Send_spec(&$spec) {
  $spec['participant_id']['api.required'] = 1;
  $spec['participant_id']['type']  = CRM_Utils_Type::T_INT;
  $spec['participant_id']['title'] = 'Participant (ID)';
  $spec['submission_id']['api.required'] = 1;
  $spec['submission_id']['type']  = CRM_Utils_Type::T_INT;
  $spec['submission_id']['title'] = 'Webform Submission (ID)';
}

/**
 * EventReceipt.Send API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_event_receipt_Send($params) {
  try{
    $sender = new CRM_Sendeventreceipt_Sender($params);
    $values = $sender->send();
    return civicrm_api3_create_success($values,$params,'EventReceipt','Send');
  }
  catch (Exception $ex) {
    Civi::log()->error($ex,'SendeventReceipt');
    return civicrm_api3_create_error('Event Receipt Send Failed - error is in the log file');
    throw new API_Exception($ex,1);
  }
}
