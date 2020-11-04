<?php

namespace Drupal\bug_report_link\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Settings form for the "Report a bug" link.
 *
 * @package Drupal\bug_report_link\Form
 */
class BugReportSettingsForm extends ConfigFormBase {

  const SETTINGS = 'bug_report_link.settings';

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'bug_report_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $form['bug_report_form_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link to bug report form'),
      '#required' => TRUE,
      '#default_value' => $config->get('bug_report_form_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $url = $form_state->getValue('bug_report_form_url');
    $link_id = $config->get('link_id');

    // Check if a menu link already exists.
    if ($link_id) {
      // Link exists; update URL.
      $bug_report_link = MenuLinkContent::load($link_id);
      $bug_report_link->link->uri = $url;
      $bug_report_link->save();
    }
    else {
      // Link does not exist; create one.
      $bug_report_link = MenuLinkContent::create([
        'title' => t('Report a bug'),
        'description' => t('Report a bug'),
        'link' => [
          'uri' => $url,
          'options' => [
            'attributes' => [
              'target' => '_blank',
              'class' => ['bug-report-link'],
            ],
          ],
        ],
        'parent' => 'system.admin',
        'weight' => 100,
      ]);
      $bug_report_link->save();
    }

    // Add this form URL and link ID to config.
    $config
      ->set('bug_report_form_url', $url)
      ->set('link_id', $bug_report_link->id())
      ->save();

    // Clear caches.
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
