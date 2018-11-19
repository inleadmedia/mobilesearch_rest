<?php

namespace AppBundle\EventListener;

use AppBundle\Document\Content;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class EntityListener.
 *
 * For available mongo odm events, see
 * https://www.doctrine-project.org/api/mongodb-odm/1.2/doc-index.html
 */
class EntityListener
{
    /**
     * @var Container
     */
    private $container;

    /**
     * EntityListener constructor.
     *
     * @param Container $container
     *   Service container object.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Listens for entity post load event.
     *
     * @param LifecycleEventArgs $event
     *   The event object.
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $entity = $event->getDocument();
        $imageFullUrl = $this->container->getParameter('mobilesearch.image_full_url');

        if (!$entity instanceof Content || !$imageFullUrl) {
            return;
        }

        $entityFields = $entity->getFields();

        // TODO: DRY
        // @see src/AppBundle/Rest/RestContentRequest.php:336
        $imageFields = [
            'field_images',
            'field_background_image',
            'field_ding_event_title_image',
            'field_ding_event_list_image',
            'field_ding_library_title_image',
            'field_ding_library_list_image',
            'field_ding_news_title_image',
            'field_ding_news_list_image',
            'field_ding_page_title_image',
            'field_ding_page_list_image',
            'field_easyscreen_image',
        ];

        $imageFields = array_intersect_key(
            array_flip($imageFields),
            $entityFields
        );

        foreach (array_keys($imageFields) as $fieldName) {
            $imageValues = $entityFields[$fieldName]['value'];

            if (is_array($imageValues)) {
                foreach ($imageValues as $k => $imageValue) {
                    // TODO: Add a test for this.
                    $entityFields[$fieldName]['value'][$k] = $this
                            ->container
                            ->get('request')
                            ->getSchemeAndHttpHost().'/web/'.$imageValue;
                }
            }
        }

        $entity->setFields($entityFields);
    }
}
