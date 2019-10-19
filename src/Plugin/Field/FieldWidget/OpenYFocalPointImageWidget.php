<?php

namespace Drupal\openy_focal_point\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\crop\Entity\Crop;
use Drupal\Core\Form\FormStateInterface;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Url;

/**
 * The difference with FocalPointImageWidget is in createPreviewLink() method
 * we use our custom route for Preview.
 */

/**
 * Plugin implementation of the 'openy_image_focal_point' widget.
 *
 * @FieldWidget(
 *   id = "openy_image_focal_point",
 *   label = @Translation("OpenY Image (Focal Point)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class OpenYFocalPointImageWidget extends FocalPointImageWidget {

  /**
   * Create the preview link form element.
   *
   * @param int $fid
   *   The fid of the image file.
   * @param string $field_name
   *   The name of the field element for the image field.
   * @param array $element_selectors
   *   The element selectors to ultimately be used by javascript.
   * @param string $default_focal_point_value
   *   The default focal point value in the form x,y.
   *
   * @return array
   *   The preview link form element.
   */
  private static function createPreviewLink($fid, $field_name, array $element_selectors, $default_focal_point_value) {
    // Replace comma (,) with an x to make javascript handling easier.
    $preview_focal_point_value = str_replace(',', 'x', $default_focal_point_value);

    // Create a token to be used during an access check on the preview page.
    $token = self::getPreviewToken();

    $preview_link = [
      '#type' => 'link',
      '#title' => new TranslatableMarkup('Preview'),
      '#url' => new Url('openy_focal_point.preview',
        [
          'fid' => $fid,
          'focal_point_value' => $preview_focal_point_value,
          'field_name' => $field_name,
        ],
        [
          'query' => ['focal_point_token' => $token],
        ]),
      '#attached' => [
        'library' => ['core/drupal.dialog.ajax'],
      ],
      '#attributes' => [
        'class' => ['focal-point-preview-link', 'use-ajax'],
        'data-selector' => $element_selectors['focal_point'],
        'data-field-name' => $field_name,
        'data-dialog-type' => 'modal',
        'target' => '_blank',
      ],
    ];

    return $preview_link;
  }

  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    if (isset($element['preview']['preview_link'])) {
      $item = $element['#value'];
      $fid = $item['fids'][0];
      $element_selectors = [
        'focal_point' => 'focal-point-' . implode('-', $element['#parents']),
      ];
      $default_focal_point_value = isset($item['focal_point']) ? $item['focal_point'] : $element['#focal_point']['offsets'];

      $element['preview']['preview_link'] = self::createPreviewLink($fid, $element['#field_name'], $element_selectors, $default_focal_point_value);
    }

    return $element;
  }

}
