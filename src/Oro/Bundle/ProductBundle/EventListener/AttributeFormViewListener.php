<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\AttributeFormViewListener as BaseAttributeFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

/**
 * This class allows to restrict moving of attributes
 */
class AttributeFormViewListener extends BaseAttributeFormViewListener
{
    /**
     * @internal
     */
    const EVENT_TYPE_VIEW = 'view';

    /**
     * @var array
     */
    private $fieldsRestrictedToMove = [
        'inventory_status',
        'images',
        'productPriceAttributesPrices',
        'shortDescriptions',
        'descriptions',
    ];

    /**
     * This property used to determine type of event inside moveFieldToBlock.
     * It's safe because it wll be cleared after event processing
     *
     * @var string
     */
    private $eventType;

    /**
     * {@inheritDoc}
     */
    public function onViewList(BeforeListRenderEvent $event)
    {
        $this->eventType = self::EVENT_TYPE_VIEW;

        parent::onViewList($event);

        $this->eventType = null;
    }

    /**
     * {@inheritDoc}
     */
    protected function moveFieldToBlock(ScrollData $scrollData, $fieldName, $blockId)
    {
        if ($this->eventType === self::EVENT_TYPE_VIEW) {
            if (in_array($fieldName, $this->getRestrictedToMoveFields(), true)) {
                return;
            }
        }

        parent::moveFieldToBlock($scrollData, $fieldName, $blockId);
    }

    /**
     * @return array
     */
    protected function getRestrictedToMoveFields()
    {
        return $this->fieldsRestrictedToMove;
    }
}
