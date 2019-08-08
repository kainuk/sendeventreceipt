<?php


class CRM_CivirulesActions_Participant_SendEventEmail extends CRM_Civirules_Action {

  /**
   * Returns a redirect url to extra data input from the user after adding a action
   *
   * Return false if you do not need extra data input
   *
   * @param int $ruleActionId
   * @return bool|string
   * @access public
   */
  public function getExtraDataInputUrl($ruleActionId) {
   // return CRM_Utils_System::url('civicrm/civirule/form/action/participant/sendeventemail', 'rule_action_id='.$ruleActionId);
    return false;
  }

  /**
   * Process the action
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   * @access public
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {

    if (!$triggerData instanceof CRM_WebformCivirules_TriggerData) {
      return FALSE;
    }
    if (empty($triggerData->getEntityData('participant'))) {
      return FALSE;
    }
    $participationId = $triggerData->getEntityData('participant')['id'];
    civicrm_api3('EventReceipt', 'send', [
      'participant_id' => $participationId,
      'submission_id' => $triggerData->getSubmissionId(),
    ]);
  }



  /**
   * This function validates whether this action works with the selected trigger.
   *
   * This function could be overriden in child classes to provide additional validation
   * whether an action is possible in the current setup.
   *
   * @param CRM_Civirules_Trigger $trigger
   * @param CRM_Civirules_BAO_Rule $rule
   * @return bool
   */
  public function doesWorkWithTrigger(CRM_Civirules_Trigger $trigger, CRM_Civirules_BAO_Rule $rule) {
    if ($trigger instanceof CRM_WebformCivirules_Trigger) {
      return true;
    }
    return false;
  }

  /**
   * Overridden parent method to process form data after submitting
   *
   * @access public
   */
  public function postProcess() {

  }

}