<?php

namespace Drupal\openy_focal_point\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\Crop;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\shortcut\ShortcutSetStorageInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create/edit crops in widget's preview popup.
 */
class OpenYFocalPointCropForm extends FormBase {

//
//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container) {
//    return new static(
//      $container->get('entity.manager')->getStorage('shortcut_set')
//    );
//  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_focal_point_crop';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $build_info = $form_state->getBuildInfo();
    $file = $build_info['args'][0];
    $image_styles = $build_info['args'][1];

    $form_state->set('file', $file);

    foreach ($image_styles as $style) {
      $form[$style->id()] = [
        '#type' => 'image_crop',
        '#file' => $file,
        '#crop_type_list' => ['crop_260_220'],
        '#crop_preview_image_style' => 'crop_thumbnail',
        '#show_default_crop' => FALSE,
        '#show_crop_area' => TRUE,
        '#warn_multiple_usages' => TRUE,
        '#crop_types_required' => ['crop_260_220'],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => '::ajaxSave',
      ],
    ];

    return $form;
  }

  public static function ajaxSave(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $form_state->get('file');

    $input = $form_state->getUserInput();
    $crop_properties = $input['prgf_teaser']['crop_wrapper']['crop_260_220']['crop_container']['values'];

    $crop_storage = \Drupal::entityTypeManager()->getStorage('crop');

    $crop = $crop_storage->create([
      'type' => 'crop_260_220',
      'entity_id' => $file->id(),
      'entity_type' => 'file',
      'uri' => $file->getFileUri(),
      'x' => (int) ($crop_properties['x'] + $crop_properties['width'] / 2),
      'y' => (int) ($crop_properties['y'] + $crop_properties['height'] / 2),
      'width' => $crop_properties['width'],
      'height' => $crop_properties['height'],
    ]);
    $crop->save();

    $ajax = new AjaxResponse();

    $ajax->addCommand(new CloseDialogCommand());

    return $ajax;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $i = 1;
  }

}
