<?php

namespace Oro\Bundle\ProductBundle\Event\Decorator;

use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

/**
 * Event decorates IndexEntityEvent to add only text information to all_text fields at the decorated event
 */
class ProductVariantIndexEntityEventDecorator extends IndexEntityEvent
{
    /** @var IndexEntityEvent */
    protected $decoratedEvent;

    /** @var integer */
    private $sourceEntityId;

    /**
     * @param IndexEntityEvent $decoratedEvent
     * @param integer $sourceEntityId
     * @param array|\object[] $childEntities
     */
    public function __construct(
        IndexEntityEvent $decoratedEvent,
        $sourceEntityId,
        array $childEntities
    ) {
        parent::__construct($decoratedEvent->getEntityClass(), $childEntities, $decoratedEvent->getContext());

        $this->decoratedEvent = $decoratedEvent;
        $this->sourceEntityId = $sourceEntityId;
    }

    /**
     * {@inheritdoc}
     */
    public function addField($entityId, $fieldName, $value, $addToAllText = false)
    {
        if (in_array($fieldName, [IndexDataProvider::ALL_TEXT_L10N_FIELD, IndexDataProvider::ALL_TEXT_FIELD], true)) {
            // proxy all_text field calls
            $this->decoratedEvent->addField($this->sourceEntityId, $fieldName, $value, $addToAllText);
        } elseif ($addToAllText) {
            // add other values to all_text only
            $this->decoratedEvent->addField(
                $this->sourceEntityId,
                IndexDataProvider::ALL_TEXT_L10N_FIELD,
                $value,
                $addToAllText
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addPlaceholderField($entityId, $fieldName, $value, $placeholders, $addToAllText = false)
    {
        if (in_array($fieldName, [IndexDataProvider::ALL_TEXT_L10N_FIELD, IndexDataProvider::ALL_TEXT_FIELD], true)) {
            // proxy all_text field calls
            $this->decoratedEvent->addPlaceholderField(
                $this->sourceEntityId,
                $fieldName,
                $value,
                $placeholders,
                $addToAllText
            );
        } elseif ($addToAllText) {
            // add other values to all_text only
            $this->decoratedEvent->addPlaceholderField(
                $this->sourceEntityId,
                IndexDataProvider::ALL_TEXT_L10N_FIELD,
                $value,
                $placeholders,
                $addToAllText
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException
     */
    public function getEntitiesData()
    {
        throw new \LogicException('Method getEntitiesData must never be called. Please, use original event instead.');
    }
}
