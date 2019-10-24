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

}
