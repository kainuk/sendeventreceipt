<?php
/**
 * Copyright (c) 2019
 * @author Klaas.Eikelboom (klaas.eikelboom@civicoop.org)
 * @license AGPL-3.0
 */

class CRM_Sendeventreceipt_Sender {

  var $_params = [];

  /**
   * CRM_Sendeventreceipt_Sender constructor.
   *
   * @param array $_params
   */
  public function __construct(array $params) {
    $this->_params = $params;
  }

  public function send(){
    $participantId = $this->_params['participant_id'];

    $participant = civicrm_api3('Participant','get',['id' => $participantId])['values'][$participantId];
    $event =  civicrm_api3('Event','get',['id' => $participant['event_id']])['values'][$participant['event_id']];


    // if the event does not have the email function configured no mail
    // has to be send, so it is done.
    if(!$event['is_email_confirm']){
      $values = array(
        'params' => array($participantId => $participant),
        'event' => $event
      );
      return $values;
    }

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

    $rset = db_query($sql,[':sid' => $this->_params['submission_id']]);

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
    return $values;
  }

}