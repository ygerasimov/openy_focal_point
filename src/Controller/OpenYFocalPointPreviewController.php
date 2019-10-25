<?php

namespace Drupal\openy_focal_point\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\focal_point\Controller\FocalPointPreviewController;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Image\ImageFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OpenYFocalPointPreviewController. We display only styles that are
 * going to be used by the formatter instead of all styles that use focal_point.
 *
 * @package Drupal\focal_point\Controller
 */
class OpenYFocalPointPreviewController extends FocalPointPreviewController {

  public function getFocalPointImageStyles() {
    $styles = explode(':', $this->request->get('image_styles'));

    return $this->entityTypeManager()->getStorage('image_style')->loadMultiple($styles);
  }

  public function content($fid, $focal_point_value) {
    $output = [];
    $file = $this->fileStorage->load($fid);
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      throw new InvalidArgumentException('The file with id = $fid is not an image.');
    }

    $styles = $this->getFocalPointImageStyles();

    // Since we are about to create a new preview of this image, we first must
    // flush the old one. This should not be a performance hit since there is
    // no good reason for anyone to preview an image unless they are changing
    // the focal point value.
    image_path_flush($image->getSource());

//    $derivative_images = [];
//    $derivative_image_note = '';
//
//    $original_image = [
//      '#theme' => 'image',
//      '#uri' => $image->getSource(),
//      '#alt' => $this->t('Focal Point Preview Image'),
//      '#attributes' => [
//        'id' => 'focal-point-preview-image',
//      ],
//    ];
//
//    if (!empty($styles)) {
//      foreach ($styles as $style) {
//        $style_label = $style->get('label');
//        $url = $this->buildUrl($style, $file, $focal_point_value);
//
//        $derivative_images[$style->getName()] = [
//          'style' => $style_label,
//          'url' => $url,
//          'image' => [
//            '#theme' => 'image',
//            '#uri' => $url,
//            '#alt' => $this->t('Focal Point Preview: %label', ['%label' => $style_label]),
//            '#attributes' => [
//              'class' => ['focal-point-derivative-preview-image'],
//            ],
//          ],
//        ];
//      }
//      $derivative_image_note = $this->t('Click an image to see a larger preview. You may need to scroll horizontally for more image styles.');
//    }
//    else {
//      // There are no styles that use a focal point effect to preview.
//      $image_styles_url = Url::fromRoute('entity.image_style.collection')->toString();
//      $this->messenger()->addWarning(
//        $this->t('You must have at least one <a href=":url">image style</a> defined that uses a focal point effect in order to preview.',
//          [':url' => $image_styles_url]
//        )
//      );
//    }
//
//    $output['focal_point_preview_page'] = [
//      '#theme' => "focal_point_preview_page",
//      "#original_image" => $original_image,
//      '#derivative_images' => $derivative_images,
//      '#focal_point' => $focal_point_value,
//      '#preview_image_note' => $this->t('This preview image above may have been scaled to fit on the page.'),
//      '#derivative_image_note' => $derivative_image_note,
//    ];

    $form = \Drupal::formBuilder()->getForm('\Drupal\openy_focal_point\Form\OpenYFocalPointCropForm', $file, $styles);
    $html = render($form);

    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '80%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new OpenModalDialogCommand($this->t('Images preview'), $html, $options)
    );

    return $response;
  }

}
