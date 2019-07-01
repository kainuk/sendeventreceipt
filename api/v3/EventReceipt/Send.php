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

    $participantId = $params['participant_id'];

    $participant = civicrm_api3('Participant','get',['id' => $participantId])['values'][$participantId];
    $event =  civicrm_api3('Event','get',['id' => $participant['event_id']])['values'][$participant['event_id']];

    $contactId = $participant['contact_id'];
    $location = array();
    if (CRM_Utils_Array::value('is_show_location',$event) == 1) {
      $locationParams = array(
        'entity_id' => $event['id'],
        'entity_table' => 'civicrm_event',
      );
      $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      CRM_Core_BAO_Address::fixAddress($location['address'][1]);
    }

    $values = array(
      'params' => array($participantId => $participant),
      'event' => $event,
      'location' => $location,
      'custom_pre_id' => null,
      'custom_post_id' => null,
      'payer' => null,
    );
    CRM_Event_BAO_Event::sendMail($contactId,$values,$participantId);
    return civicrm_api3_create_success($values,$params,'EventReceipt','Send');
  }
  catch (Exception $ex) {
    throw new API_Exception($ex,1);
  }
}
