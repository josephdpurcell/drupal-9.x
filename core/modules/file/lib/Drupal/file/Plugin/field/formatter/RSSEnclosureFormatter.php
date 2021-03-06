<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\formatter\RSSEnclosureFormatter.
 */

namespace Drupal\file\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'file_rss_enclosure' formatter.
 *
 * @Plugin(
 *   id = "file_rss_enclosure",
 *   module = "file",
 *   label = @Translation("RSS enclosure"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class RSSEnclosureFormatter extends FormatterBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {

    // Add the first file as an enclosure to the RSS item. RSS allows only one
    // enclosure per item. See: http://en.wikipedia.org/wiki/RSS_enclosure
    foreach ($items as $item) {
      if ($item['display']) {
        $entity->rss_elements[] = array(
          'key' => 'enclosure',
          'attributes' => array(
            'url' => file_create_url($item['uri']),
            'length' => $item['filesize'],
            'type' => $item['filemime'],
          ),
        );
        break;
      }
    }

  }

}
