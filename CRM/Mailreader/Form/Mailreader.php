<?php

/*
  +--------------------------------------------------------------------+
  |                                                                    |
  | Developed by: Vangelis Pantazis                                    |
  | @ iXiam Global Solutions (info@ixiam.com)                          |
  | Last Modification Date: 2018-04-30                                 |
  |                                                                    |
  +--------------------------------------------------------------------+
  | Copyright iXiam Global Solutions (c) 2007-2018                     |
  | http://www.ixiam.com                                               |
  +--------------------------------------------------------------------+
 */

class CRM_Mailreader_Form_Mailreader extends CRM_Core_Form {

  public $_action;

  function buildQuickForm() {

    // Variables
    $params = $data = $this->exportValues();
    $warning = '';
    $skip = FALSE;

    // Set page title
    CRM_Utils_System::setTitle(ts('Stored Email viewer'));

    // Fetch the mailing preferences
    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'mailing_backend');

    if ($mailingInfo['outBound_option'] != '5') {
      // Issue a warning and do nothing
      $url = CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1');
      $warning = ts('Your <a href="%1">CiviCRM mailer option</a> is not set to <strong>"Redirect to Database"</strong>. I got nothing to show you.', array("1" => $url, 'domain' => 'com.ixiam.modules.mailreader'));
      $skip = TRUE;
    }
    else {
      // Limit to X rows by default
      $this->add('text', 'limit_to', ts('Limit to: ', array('domain' => 'com.ixiam.modules.mailreader')), array('size' => 6, 'maxlength' => 6));
      // Show last rows first, by default
      $this->add('checkbox', 'show_last', ts('Show in DESCending order', array('domain' => 'com.ixiam.modules.mailreader')));
      // Count how many rows do we have
      $countrows = $this->countlog();
      // Populate the table
      $limit_to = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
      if (isset($_GET['order']) && $_GET['order'] == 'ASC') {
        $sort_order = FALSE;
      }
      elseif (isset($_GET['order']) && ($_GET['order'] == 'DESC') || !isset($_GET['order'])) {
        $sort_order = TRUE;
      }
      if ($limit_to) {
        $records = $this->readlog($limit_to, 0, $sort_order, FALSE);
      }
      else {
        // Default value-limit to 10
        $records = $this->readlog(10, 0, $sort_order, FALSE);
      }
      // Add the export button
      $this->addButtons(array(
        array(
          'type' => 'refresh',
          'subName' => 'refresh',
          'name' => ts('Refresh listing', array('domain' => 'com.ixiam.modules.mailreader')),
        ),
        array(
          'type' => 'submit',
          'subName' => 'delete_all',
          'name' => ts('Delete ALL entries', array('domain' => 'com.ixiam.modules.mailreader')),
        ),
      ));

      // Assign values to template
      $this->assign('limit_to', $limit_to);
      $this->assign('records', $records);
      $this->assign('skip', $skip);
      $this->assign('total_rows', $countrows);
      if (isset($show_last)) {
        $this->assign('show_last', $show_last);
      }
    }
    // We need this variable in all cases
    $this->assign('warning', $warning);
    if (!$skip) {
      $this->setDefaults();
    }
    parent::buildQuickForm();
  }

  /**
   * This function sets the default values for the form.
   * default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {

    $limit_to = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    // Predefined value
    $defaults['show_last'] = TRUE;

    if (( isset($_GET['order']) && $_GET['order'] == 'DESC' ) || !isset($_GET['order'])) {
      $defaults['show_last'] = TRUE;
    }
    else {
      $defaults['show_last'] = FALSE;
    }
    if ($limit_to) {
      $defaults['limit_to'] = $limit_to;
    }
    else {
      $defaults['limit_to'] = 10;
    }

    return $defaults;
  }

  public function postProcess() {
    $params = $this->exportValues();

    if (isset($params['_qf_Mailreader_submit_delete_all'])) {
      // We will now be deleting all the log entries
      $this->deletelog();
      // After deletion, redirect
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/emailviewer', '&reset=1'));
    }

    if (isset($params['limit_to']) && isset($params['_qf_Mailreader_refresh_refresh'])) {

      $urlParams = 'limit=' . $params['limit_to'];
      if (isset($params['show_last'])) {
        $urlParams .= '&order=DESC';
      }
      else {
        $urlParams .= '&order=ASC';
      }
      $urlParams .= '&reset=1';
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/emailviewer', $urlParams));
    }

    // Export the selected email into .eml format
    if (isset($params['_qf_Mailreader_submit_export_eml'])) {
      $exportable_id = CRM_Utils_Request::retrieve('mailreader_email_id', 'Integer', $this);
      $selected_record = $this->readlog($exportable_id, 0, TRUE, TRUE);
      $exportable_filename = 'mail_' . date_timestamp_get(date_create()) . '.eml';
      $output_file = self::write_to_csv($selected_record, 'mailreader', $exportable_filename);

      $status = ts('Right click <a href="%1">here</a> and "Save target as ..." to save the exported EML file', array(1 => $output_file));
      CRM_Core_Session::setStatus($status, ts("Exported to EML", array()), 'success', array('expires' => 0));
    }
  }

  /**
   * This function returns the stored entries from a table.
   *
   * @access private
   * @param int   $count
   *        int   $offset
   *        bool  $order Sort order: TRUE = DESC / FALSE = ASC
   *
   * @return array (
    id,
    job_id,
    recipient_email,
    headers,
    body,
    added_at,
    removed_at
    )
   *
   * */
  private function readlog($count, $offset, $order, $single) {

    if ($order) {
      $sort_order = 'DESC';
    }
    else {
      $sort_order = 'ASC';
    }
    $sql_default = "
    SELECT  id,
            job_id,
            recipient_email,
            headers,
            body,
            added_at,
            removed_at
    FROM civicrm_mailing_spool";

    $sql = $sql_default . " ORDER BY id {$sort_order}";

    if ($count) {
      $sql .= " LIMIT {$count}";
    }
    if ($offset) {
      $sql .= " OFFSET {$offset}";
    }

    // If $single is TRUE, $count is taking the role of column 'id'
    if ($single) {
      $sql = $sql_default . " WHERE id = {$count}";
    }

    $fsql = CRM_Core_DAO::executeQuery($sql);
    $entries = array();

    while ($fsql->fetch()) {
      $entries[$fsql->id]['id'] = $fsql->id;
      $entries[$fsql->id]['job_id'] = $fsql->job_id;
      $entries[$fsql->id]['recipient_email'] = $fsql->recipient_email;
      $entries[$fsql->id]['headers'] = $fsql->headers;
      $entries[$fsql->id]['body'] = $fsql->body;
      $entries[$fsql->id]['added_at'] = $fsql->added_at;
      $entries[$fsql->id]['removed_at'] = $fsql->removed_at;
    }

    return $entries;
  }

  /**
   * Private function that will delete ALL rows from table civicrm_mailing_spool
   *
   */
  private function deletelog() {
    $sql = "DELETE FROM civicrm_mailing_spool";
    $fsql = CRM_Core_DAO::executeQuery($sql);

    return TRUE;
  }

  /**
   * Private function that counts how many rows we have
   *
   */
  private function countlog() {
    $sql = "SELECT count(*) as countrows FROM civicrm_mailing_spool";
    $fsql = CRM_Core_DAO::executeQuery($sql);
    while ($fsql->fetch()) {
      $count = $fsql->countrows;
    }
    return $count;
  }

  /**
   * WIP : Export selected email to file
   * Writes defined output to CSV file
   * @access private
   * @param  array $data  - data to write
   *         array $export_dir - export path
   *         string $eml_filename - Output filename
   *
   * @return string $display_url
   *
   */
  private function write_to_csv($data, $export_dir, $eml_filename) {

    // Variable declaration
    $config = CRM_Core_Config::singleton();
    $export_path = $config->imageUploadDir . $export_dir . "/";

    // If export path doesn't exist, create it
    if (!is_dir($export_path)) {
      // Check directory, if not, create it. TODO: Review folder permissions
      mkdir($export_path, 0775, true);
    }

    if (is_array($data)) {
      // We are interested only on the first value
      $data = array_shift($data);
      // Lets construct the full EML, by joing the headers with the body
      $eml_data = $data['headers'] . $data['body'];
      // Write that file
      file_put_contents($export_path . $eml_filename, $eml_data . PHP_EOL, FILE_APPEND);
      $display_url = $config->userFrameworkBaseURL . strstr($config->imageUploadDir, 'sites') . $export_dir . "/" . $eml_filename;
      // Return the full rendered URL of the file
      return $display_url;
    }
  }

}
