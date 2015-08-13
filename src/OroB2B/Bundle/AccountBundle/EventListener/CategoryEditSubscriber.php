<?php
namespace OroB2B\Bundle\AccountBundle\EventListener;

use OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CategoryEditSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [CategoryEditEvent::NAME => 'onCategoryEdit'];
    }

    /**
     * @param CategoryEditEvent $event
     */
    public function onCategoryEdit(CategoryEditEvent $event)
    {
        var_dump($event); die();
    }
}
