<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent;

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
        /**
         * TODO: implement
         */
    }
}
