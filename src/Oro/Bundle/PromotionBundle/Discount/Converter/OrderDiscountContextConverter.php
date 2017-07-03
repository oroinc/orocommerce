<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class OrderDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @var LineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;
    /**
     * @var DiscountContextConverterRegistry
     */
    private $converter;
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * OrderDiscountContextConverter constructor.
     * @param LineItemsToDiscountLineItemsConverter $lineItemsConverter
     */
    public function __construct(
        LineItemsToDiscountLineItemsConverter $lineItemsConverter,
        DiscountContextConverterInterface $converter,
        DoctrineHelper $doctrineHelper
    ) {
        $this->lineItemsConverter = $lineItemsConverter;
        $this->converter = $converter;
        $this->doctrineHelper = $doctrineHelper;
    }

    /** {@inheritdoc} */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof Order;
    }

    /**
     * {@inheritdoc}
     * @param Order $entity
     */
    public function convert($entity): DiscountContext
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($entity))
            );
        }

        if (!$entity->getId()) {
            $sourceEntity = $this->doctrineHelper->getEntity(
                $entity->getSourceEntityClass(),
                $entity->getSourceEntityId()
            );
            if (!$sourceEntity) {
                throw new UnsupportedSourceEntityException('Cant convert empty Order into DiscountContext');
            }
            return $this->converter->convert($sourceEntity);
        }

        $discountContext = new DiscountContext();
        $discountContext->setSubtotal($entity->getSubtotal());
        $discountContext->setLineItems($this->lineItemsConverter->convert($entity->getLineItems()->toArray()));
        return $discountContext;

    }
}
