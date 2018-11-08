<?php
use CRM_Noemailablecontacts_ExtensionUtil as E;

class CRM_Noemailablecontacts_Form_Report_Noemaialblecontacts extends CRM_Report_Form {

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;
  
  protected $_newRowCount = NULL;

  protected $_customGroupExtends = array('Membership');
  protected $_customGroupGroupBy = FALSE; function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => E::ts('Organization'),
            'required' => TRUE,
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'first_name' => array(
            'title' => E::ts('First Name'),
            'no_repeat' => TRUE,
            'no_display' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'last_name' => array(
            'title' => E::ts('Last Name'),
            'no_repeat' => TRUE,
            'no_display' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => E::ts('Organization'),
            'operator' => 'like',
          ),
          'id' => array(
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => array('title' => E::ts('State/Province')),
          'country_id' => array('title' => E::ts('Country')),
        ),
         'filters' => array(
          'street_number' => array(
            'title' => ts('Street Number'),
            'type' => CRM_Utils_Type::T_INT,
            'name' => 'street_number',
          ),
          'street_name' => array(
            'title' => ts('Street Name'),
            'name' => 'street_name',
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'postal_code' => array(
            'title' => ts('Postal Code'),
            'type' => CRM_Utils_Type::T_STRING,
            'name' => 'postal_code',
          ),
          'city' => array(
            'title' => ts('City'),
            'type' => CRM_Utils_Type::T_STRING,
            'name' => 'city',
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ),
          'country_id' => array(
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => array(
          'contact_id_a' => array(
            'title' => ts('Related Contacts'),
            'no_display' => TRUE,
          ),
        ),
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
    );
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', E::ts('Related Contacts Report'));
    parent::preProcess();
    $options = array(1=>'Emailable', 2=>'Un-emailable');
    $this->_filters['civicrm_contact']['emailability'] = array();
    $this->_filters['civicrm_contact']['emailability']['title'] = 'Emailability';
    $this->_filters['civicrm_contact']['emailability']['type'] = CRM_Utils_Type::T_INT;
    $this->_filters['civicrm_contact']['emailability']['operatorType'] = CRM_Report_Form::OP_MULTISELECT;
    $this->_filters['civicrm_contact']['emailability']['options'] =  $options;   
  }

  function select() {
    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }
    $this->_columnHeaders["{$this->_aliases['civicrm_contact']}_sort_name_emailable_count"]['title'] = 'Emailable related contacts';
    
    $this->_columnHeaders["{$this->_aliases['civicrm_contact']}_sort_name_noemailable_count"]['title'] = 'Un-emailable related contacts';
    $select[] = "GROUP_CONCAT(distinct {$this->_aliases['civicrm_relationship']}.contact_id_a) as {$this->_aliases['civicrm_contact']}_sort_name_emailable_count";
    $select[] = "GROUP_CONCAT(distinct {$this->_aliases['civicrm_relationship']}.contact_id_a) as {$this->_aliases['civicrm_contact']}_sort_name_noemailable_count";
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
         FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
                LEFT JOIN civicrm_relationship {$this->_aliases['civicrm_relationship']}
                          ON {$this->_aliases['civicrm_contact']}.id =
                             {$this->_aliases['civicrm_relationship']}.contact_id_b
                LEFT JOIN civicrm_contact cc2
                          ON {$this->_aliases['civicrm_relationship']}.contact_id_a =
                             cc2.id";


    //address field
    $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                       ON {$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_address']}.contact_id AND
                          {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    //used when email field is selected
    if ($this->_emailField) {
      $this->_from .= "
              LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                        ON {$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id AND
                           {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    $clauses[] = "{$this->_aliases['civicrm_contact']}.contact_type = 'Organization'";
    
    $hold_clauses = array();
    $hold_clauses[] = "cc2.do_not_email = 1";
    $hold_clauses[] = "{$this->_aliases['civicrm_email']}.on_hold != 0";

    //$clauses[] = "(".(!empty($hold_clauses) ? implode(' OR ', $hold_clauses) : '(1)').")";
    
    
    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_relationship']}.contact_id_b";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery(TRUE);

    $rows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();
    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row
        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (CRM_Utils_Array::value($colName, $checkList) &&
            is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }
      $related_contacts = '';
      if (array_key_exists("{$this->_aliases['civicrm_contact']}_sort_name_emailable_count", $row) ) {
        $related_contacts = $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_emailable_count"];
      }
      else  if (array_key_exists("{$this->_aliases['civicrm_contact']}_sort_name_noemailable_count", $row) ) {
        $related_contacts = $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_noemailable_count"];
      }
      if( !empty($related_contacts) ){
        $related_contacts_array = explode(",",$related_contacts);
        $total_record = count($related_contacts_array);
        $email_able_data_array = array();
        $no_email_able_data_array = array();
        $email_able = "SELECT GROUP_CONCAT(id) as cid from civicrm_contact WHERE do_not_email = 0 AND id IN ($related_contacts)";
        $dao = CRM_Core_DAO::executeQuery($email_able);
        while ($dao->fetch()) {
          if($dao->cid){
            $email_able_data = $dao->cid;
            $email_able_data_array = explode(",",$email_able_data);
            $no_email_able_data_array = array_diff($related_contacts_array, $email_able_data_array);
            
            $on_hold_email = "SELECT GROUP_CONCAT(contact_id) as contact_id from civicrm_email WHERE on_hold = 1 AND contact_id IN ($email_able_data)";
            $dao_no_email = CRM_Core_DAO::executeQuery($on_hold_email);
            while ($dao_no_email->fetch()) {
              if($dao_no_email->contact_id){
                $on_hold_data = $dao_no_email->contact_id;
                $on_hold_data_array = explode(",",$on_hold_data);
                $email_able_data_array = array_diff($email_able_data_array, $on_hold_data_array);
                $no_email_able_data_array = array_merge($on_hold_data_array, $no_email_able_data_array);
              }
            }
          }
          else{
            $no_email_able_data_array = $related_contacts_array;
          }
        }
        
        $final_emailable_array = count(array_unique($email_able_data_array));
        $final_no_emailable_array = count(array_unique($no_email_able_data_array));
        if($final_emailable_array == 0) $final_emailable_array = '0';
        if($final_no_emailable_array == 0) $final_no_emailable_array = '0';
        $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_emailable_count"] = $final_emailable_array;
        $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_noemailable_count"] = $final_no_emailable_array;
      }
      else{
        $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_emailable_count"] = '0';
        $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_noemailable_count"] = '0';
      }
      
      
      $emailable_count = $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_emailable_count"];
      $unemailable_count = $rows[$rowNum]["{$this->_aliases['civicrm_contact']}_sort_name_noemailable_count"];
      if(!empty($this->_params['emailability_value']) ){
        $emailability_value = $this->_params['emailability_value'];
        if($this->_params['emailability_op'] == 'in'){
          if(!in_array(1, $emailability_value) && $unemailable_count == 0){
            unset($rows[$rowNum]);
          }
          if(!in_array(2, $emailability_value) && $emailable_count == 0){
             unset($rows[$rowNum]);
          }
        }
        else{
          if(in_array(1, $emailability_value)&& $unemailable_count == 0){
            unset($rows[$rowNum]);
          }
          if(in_array(2, $emailability_value) && $emailable_count == 0){
             unset($rows[$rowNum]);
          }
        }
      }
      if (!$entryFound) {
        break;
      }
      $this->_newRowCount = count($rows);
    }
  }
  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    if($statistics['counts']['rowsFound']){
      $statistics['counts']['rowsFound']['value'] = $this->_newRowCount;
    }
    return $statistics;
  }

}
