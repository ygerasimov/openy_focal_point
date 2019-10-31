<?php

namespace Drupal\openy_focal_point\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\Crop;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Form to create/edit crops in widget's preview popup.
 */
class OpenYFocalPointEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_focal_point_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    /** @var File $file */
    $file = $build_info['args'][0];
    $image_styles = $build_info['args'][1];
    $focal_point_value = $build_info['args'][2];

    $form_state->set('file', $file);

    $random = new Random();

    foreach ($image_styles as $style) {
      $style_label = $style->get('label');
      // We add random to get parameter so everytime Preview popup is loaded
      // fresh images are regenerated and browser cache is bypassed. So if
      // we edit crop settings, save them and open Preview popup once again
      // images are regenerated.
      $focal_point_value .= '-' . $random->name();
      $url = $this->buildUrl($style, $file, $focal_point_value);

      $derivative_images[$style->id()] = [
        'style' => $style_label,
        'url' => $url,
        'image' => [
          '#theme' => 'image',
          '#uri' => $url,
          '#alt' => $this->t('OpenY Focal Point Preview: %label', ['%label' => $style_label]),
          '#attributes' => [
            'class' => ['focal-point-derivative-preview-image'],
          ],
        ],
      ];

      $form['openy_focal_point_preview'] = [
        '#theme' => "openy_focal_point_preview",
        '#data' => [
          'derivative_images' => $derivative_images,
        ]
      ];

      // From FocalPointImageWidget::createFocalPointField().
      $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');
      $crop = Crop::findCrop($file->getFileUri(), $crop_type);
      $image = \Drupal::service('image.factory')->get($file->getFileUri());
      $width = $image->getWidth();
      $height = $image->getHeight();

      $anchor = \Drupal::service('focal_point.manager')
        ->absoluteToRelative($crop->x->value, $crop->y->value, $width, $height);
      $focal_point_default_value = "{$anchor['x']},{$anchor['y']}";

      $form['focal'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Set focal point'),
      ];
      // Should be unique class for textfield.
      $focal_point_selector = 'focal-point-' . $style->id();

      $form['focal'][$style->id()]['indicator'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['focal-point-indicator'],
          'data-selector' => $focal_point_selector,
//          'data-delta' => $delta,
        ],
      ];

      $form['focal'][$style->id()]['preview'] = [
        '#theme' => 'image_style',
        '#width' => $width,
        '#height' => $height,
        // @TODO: Avoid hardcoded style name. It comes from field settings.
        '#style_name' => 'thumbnail_focal_point',
        '#uri' => $file->getFileUri(),
      ];

      $form['focal'][$style->id()]['focal_point'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Focal point'),
        '#description' => $this->t('Specify the focus of this image in the form "leftoffset,topoffset" where offsets are in percents. Ex: 25,75'),
        '#default_value' => $focal_point_default_value,
//        '#element_validate' => [[static::class, 'validateFocalPoint']],
        '#attributes' => [
          'class' => ['focal-point', $focal_point_selector],
          'data-selector' => $focal_point_selector,
//          'data-field-name' => $field_name,
        ],
        '#wrapper_attributes' => [
          'class' => ['focal-point-wrapper', 'visually-hidden', 'hidden'],
        ],
        '#attached' => [
          'library' => ['focal_point/drupal.focal_point'],
        ],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Focal Point'),
      '#ajax' => [
        'callback' => '::ajaxSave',
      ],
    ];

    $form['#attached']['library'][] = 'openy_focal_point/openy_focal_point';

    return $form;
  }

  public static function ajaxSave(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $form_state->get('file');

    $crop_type = \Drupal::config('focal_point.settings')->get('crop_type');
    $crop = Crop::findCrop($file->getFileUri(), $crop_type);

    /** @var \Drupal\focal_point\FocalPointManagerInterface $focal_point_manager */
    $focal_point_manager = \Drupal::service('focal_point.manager');
    list($x, $y) = explode(',', $form_state->getValue('focal_point'));
    $image = \Drupal::service('image.factory')->get($file->getFileUri());
    $width = $image->getWidth();
    $height = $image->getHeight();

    $focal_point_manager->saveCropEntity($x, $y, $width, $height, $crop);

    $ajax = new AjaxResponse();
    $ajax->addCommand(new CloseDialogCommand());

    return $ajax;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Create the URL for a preview image including a query parameter.
   *
   * @param \Drupal\image\Entity\ImageStyle $style
   *   The image style being previewed.
   * @param \Drupal\file\Entity\File $image
   *   The image being previewed.
   * @param string $focal_point_value
   *   The focal point being previewed in the format XxY where x and y are the
   *   left and top offsets in percentages.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The URL of the preview image.
   */
  protected function buildUrl(ImageStyle $style, File $image, $focal_point_value) {
    $url = $style->buildUrl($image->getFileUri());
    // It is important to not use focal_point_preview_value query parameter as
    // it is used by FocalPointEffectBase for preview so our focal point
    // values will be overidden.
    $url .= (strpos($url, '?') !== FALSE ? '&' : '?') . 'bypass_browser_cache=' . $focal_point_value;

    return $url;
  }

}
