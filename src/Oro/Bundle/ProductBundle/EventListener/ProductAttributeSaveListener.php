<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\BeforeEntityAttributeSaveEvent;

class ProductAttributeSaveListener
{
    const ALIAS = 'product';
    const YES_AND_DO_NOT_DISPLAY_OPTION_ID = 3;

    /**
     * @param BeforeEntityAttributeSaveEvent $event
     */
    public function onBeforeSave(BeforeEntityAttributeSaveEvent $event)
    {
        if ($event->getAlias() === self::ALIAS) {
            $options = $event->getOptions();
            $options['datagrid'] = ['is_visible' => self::YES_AND_DO_NOT_DISPLAY_OPTION_ID];

            $event->setOptions($options);
        }
    }
}
