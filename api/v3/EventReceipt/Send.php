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

    $participantId = $params['participant_id'];

    $participant = civicrm_api3('Participant','get',['id' => $participantId])['values'][$participantId];
    $event =  civicrm_api3('Event','get',['id' => $participant['event_id']])['values'][$participant['event_id']];
    $location = array();
    if (CRM_Utils_Array::value('is_show_location',$event) == 1) {
      $locationParams = array(
        'entity_id' => $event['id'],
        'entity_table' => 'civicrm_event',
      );
      $location = CRM_Core_BAO_Location::getValues($locationParams, TRUE);
      CRM_Core_BAO_Address::fixAddress($location['address'][1]);
    }

    $lineItem[] = CRM_Price_BAO_LineItem::getLineItems($participantId);

    $cgs = civicrm_api3('CustomGroup', 'get', [
      'extends' => "participant",
    ])['values'];

    $customGroup = [];

    $sql = <<<SQL
    select data,form_key from {webform_submitted_data} d
    join {webform_component} c on (c.cid = d.cid) and (c.nid = d.nid)
    where sid=:sid and form_key like '%participant_1_cg%custom%'
SQL;

    $rset = db_query($sql,[':sid' => $params['submission_id']]);

    while ($record = $rset->fetchAssoc()) {
      $form_key = $record['form_key'];
      $data = $record['data'];
      list(,,,,$cgId,,$fieldID) = explode('_',$form_key);
      $cgId = substr($cgId,2);
      $customFields[$fieldID]['id'] = $fieldID;
      try {
          $label = civicrm_api3('CustomField', 'getvalue', [
              'return' => "label",
              'id' => $fieldID,
          ]);
          $formattedValue = CRM_Core_BAO_CustomField::displayValue($data, $fieldID, $participantId);
          $customGroup[$cgs[$cgId]['title']][$label] = str_replace('&nbsp;', '', $formattedValue);
      } catch (Exception $ex) {
          // the fieldId is not found in Civi (so it is not added to the customGroups
      }
    }

    $template = CRM_Core_Smarty::singleton();
    $template->assign('customGroup',$customGroup);
    $template->assign('lineItem',$lineItem);

    $values = array(
      'params' => array($participantId => $participant),
      'event' => $event,
      'location' => $location,
      'custom_pre_id' => null,
      'custom_post_id' => null,
      'payer' => null,
      'customGroup' => $customGroup,
    );
    CRM_Event_BAO_Event::sendMail($participant['contact_id'],$values,$participantId);
    return civicrm_api3_create_success($values,$params,'EventReceipt','Send');
  }
  catch (Exception $ex) {
    throw new API_Exception($ex,1);
  }
}
