<?php

/**
 * @file
 * Contains Drupal\views_ui\ViewPreviewFormController.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityInterface;

/**
 * Form controller for the Views preview form.
 */
class ViewPreviewFormController extends ViewFormControllerBase {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state, EntityInterface $view) {
    $form['#prefix'] = '<div id="views-preview-wrapper" class="views-admin clearfix">';
    $form['#suffix'] = '</div>';
    $form['#id'] = 'views-ui-preview-form';

    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    $seen_ids_init = &drupal_static('drupal_html_id:init');
    $seen_ids_init = array();

    $form_state['no_cache'] = TRUE;

    $form['controls']['#attributes'] = array('class' => array('clearfix'));

    // Add a checkbox controlling whether or not this display auto-previews.
    $form['controls']['live_preview'] = array(
      '#type' => 'checkbox',
      '#id' => 'edit-displays-live-preview',
      '#title' => t('Auto preview'),
      '#default_value' => config('views.settings')->get('ui.always_live_preview'),
    );

    // Add the arguments textfield
    $form['controls']['view_args'] = array(
      '#type' => 'textfield',
      '#title' => t('Preview with contextual filters:'),
      '#description' => t('Separate contextual filter values with a "/". For example, %example.', array('%example' => '40/12/10')),
      '#id' => 'preview-args',
    );

    $args = array();
    if (!empty($form_state['values']['view_args'])) {
      $args = explode('/', $form_state['values']['view_args']);
    }

    if ($view->renderPreview) {
      $form['preview'] = array(
        '#weight' => 110,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('id' => 'views-live-preview'),
        '#markup' => $view->renderPreview($view->displayID, $args),
      );
    }
    $form['#action'] = url('admin/structure/views/view/' . $view->get('name') .'/preview/' . $view->displayID);

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $view = $this->getEntity($form_state);
    return array(
      '#attributes' => array(
        'id' => 'preview-submit-wrapper',
      ),
      'button' => array(
        '#type' => 'submit',
        '#value' => t('Update preview'),
        '#attributes' => array('class' => array('arguments-preview')),
        '#submit' => array(array($this, 'submitPreview')),
        '#id' => 'preview-submit',
        '#ajax' => array(
          'path' => 'admin/structure/views/view/' . $view->get('name') . '/preview/' . $view->displayID . '/ajax',
          'wrapper' => 'views-preview-wrapper',
          'event' => 'click',
          'progress' => array('type' => 'throbber'),
          'method' => 'replace',
        ),
      ),
    );
  }

  /**
   * Form submission handler for the Preview button.
   */
  public function submitPreview($form, &$form_state) {
    // Rebuild the form with a pristine $view object.
    $view = $this->getEntity($form_state);
    $form_state['build_info']['args'][0] = views_ui_cache_load($view->get('name'));
    $view->renderPreview = TRUE;
    $form_state['show_preview'] = TRUE;
    $form_state['rebuild'] = TRUE;
  }

}
