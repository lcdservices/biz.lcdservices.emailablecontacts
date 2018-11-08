<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Noemailablecontacts_Form_Report_Noemailablecontacts',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'Emailable Contacts',
      'description' => 'Report template to generate list of emailable/non-emailable contacts by organizations.',
      'class_name' => 'CRM_Noemailablecontacts_Form_Report_Noemailablecontacts',
      'report_url' => 'biz.lcdservices.noemailablecontacts/noemailablecontacts',
      'component' => 'Contact',
    ),
  ),
);
