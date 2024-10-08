<?php

require_once 'altinvoice.civix.php';
use CRM_Altinvoice_ExtensionUtil as E;


function altinvoice_civicrm_alterMailParams(&$params, $context) {
  if ($context == 'singleEmail' && $params['valueName'] == 'contribution_invoice_receipt') {
    // Get relationship types that get an alternate invoice.
    $invoiceCustomFieldId = civicrm_api3('CustomField', 'getvalue', [
      'name' => 'invoice_relationship',
      'return' => 'id',
    ]);
    $result = civicrm_api3('RelationshipType', 'get', [
      'return' => ["id"],
      'custom_' . $invoiceCustomFieldId => 1,
      'is_active' => 1,
    ])['values'];
    $relTypes = array_keys($result);
    if ($relTypes) {
      // Get the related contacts in one direction
      $contacts1 = civicrm_api3('Relationship', 'get', [
        'sequential' => 1,
        'return' => ["contact_id_b"],
        'contact_id_a' => $params['contactId'],
        'relationship_type_id' => ['IN' => $relTypes],
      ])['values'];
      $alternateContacts = CRM_Utils_Array::collect('contact_id_b', $contacts1);
      $contacts2 = civicrm_api3('Relationship', 'get', [
        'sequential' => 1,
        'return' => ["contact_id_a"],
        'contact_id_b' => $params['contactId'],
        'relationship_type_id' => ['IN' => $relTypes],
      ])['values'];
      $alternateContacts = array_merge($alternateContacts, CRM_Utils_Array::collect('contact_id_a', $contacts2));
      // NOTE: This gets primary, not billing emails - but so does core.  See CRM-17784.
      if ($alternateContacts) {
        $altEmailRecords = civicrm_api3('Email', 'get', [
          'sequential' => 1,
          'return' => ["email"],
          'contact_id' => ['IN' => $alternateContacts],
          'is_primary' => 1,
        ])['values'];
        $altEmails = CRM_Utils_Array::collect('email', $altEmailRecords);
        $altEmails = rtrim(implode(',', $altEmails), ',');
        $params['cc'] = $altEmails;
      }
    }

    // Hack in a link to the invoice ID, if the altinvoice_include_link_to_pay setting is on.
    $isLinkFlagSet = Civi::settings()->get('altinvoice_include_link_to_pay');
    if ($context == 'singleEmail' && $params['valueName'] == 'contribution_invoice_receipt' && $isLinkFlagSet) {
      $checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($params['contactId']);
      $urlParams = [
        'reset' => 1,
        'id' => $params['contactId'],
        'cs' => $checksum,
      ];
      $payUrl = CRM_Utils_System::url('civicrm/user', $urlParams, TRUE);
      $params['text'] .= "\nClick here to pay this invoice: $payUrl";
      $params['html'] .= "<p>Click here to <a href='$payUrl'>pay this invoice</a>.</p>";
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function altinvoice_civicrm_config(&$config) {
  _altinvoice_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function altinvoice_civicrm_install() {
  _altinvoice_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function altinvoice_civicrm_enable() {
  _altinvoice_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_buildForm()
 */
function altinvoice_civicrm_buildForm($formName, &$form) {
  if ($formName === 'CRM_Admin_Form_Preferences_Contribute') {
    $formBuilder = new CRM_Altinvoice_Hook_BuildForm_PreferencesContribute($form);
    $formBuilder->buildForm();
  }
}

/**
 * Implements hook_civicrm_postProcess()
 */
function altinvoice_civicrm_postProcess($formName, &$form) {
  if ($formName === 'CRM_Admin_Form_Preferences_Contribute') {
    $formPostProcessor = new CRM_Altinvoice_Hook_PostProcess_PreferencesContribute($form);
    $formPostProcessor->postProcess();
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function altinvoice_civicrm_navigationMenu(&$menu) {
  _altinvoice_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _altinvoice_civix_navigationMenu($menu);
} // */
