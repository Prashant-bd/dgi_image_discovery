<?

namespace Drupal\dgi_image_discovery_cache\EventSubscriber;

use Drupal\dgi_image_discovery_cache\ServiceInterface;
use Drupal\dgi_image_discovery\EventSubscriber\AbstractImageDiscoverySubscriber;
use Drupal\dgi_image_discovery\ImageDiscoveryEvent;
use Drupal\node\NodeInterface;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheDiscoverySubscriber extends AbstractImageDiscoverySubscriber {

  const PRIORITY = 900;

  protected EntityStorageInterface $mediaStorage;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->mediaStorage = $entity_type_manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public function discoverImage(ImageDiscoveryImage $event) : void {
    $node = $event->getEntity();

    if (!($node instanceof NodeInterface)) {
      return;
    }

    $results = $this->mediaStorage->getQuery()
      ->condition('field_media_of', $node->id())
      ->condition('field_media_use.entity:taxonomy_term.field_external_uri.uri', 'http://pcdm.org/use#ThumbnailImage')
      ->accessCheck()
      ->range(0, 1)
      ->execute();

    $event->addCacheTags(['media_list']);

    if ($results) {
      $media = $this->mediaStorage->load(reset($results));

      $event->addCacheableDependency($media->access('view', NULL, TRUE))
        ->setMedia($media)
        ->stopPropagation();
    }
  }

}
