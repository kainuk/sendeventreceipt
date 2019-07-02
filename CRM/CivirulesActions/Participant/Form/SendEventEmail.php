<?php


class CRM_CivirulesActions_Participant_Form_SendEventEmail extends CRM_CivirulesActions_Form_Form {

  public function buildQuickForm() {
    $this->add('hidden', 'rule_action_id');
    $this->add('select', 'template_id', ts('Email Send Template'), ['' => ts('-- please select --')] + CRM_Core_OptionGroup::values('msg_tpl_workflow_event'), TRUE);
    $this->addButtons(array(
      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE,),
      array('type' => 'cancel', 'name' => ts('Cancel'))));
  }

  public function setDefaultValues() {
    $defaultValues = parent::setDefaultValues();
    $data = unserialize($this->ruleAction->action_params);
    if (!empty($data['template_id'])) {
      $defaultValues['template_id'] = $data['template_id'];
    }
    return $defaultValues;
  }

  /**
   * Overridden parent method to process form data after submitting
   *
   * @access public
   */
  public function postProcess() {
    $data['template_id'] = $this->_submitValues['template_id'];
    $this->ruleAction->action_params = serialize($data);
    $this->ruleAction->save();
    parent::postProcess();
  }


}