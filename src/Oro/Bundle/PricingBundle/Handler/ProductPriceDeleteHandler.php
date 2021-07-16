<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandler;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

/**
 * The delete handler for ProductPrice entity.
 */
class ProductPriceDeleteHandler extends AbstractEntityDeleteHandler
{
    /** @var PriceManager */
    private $priceManager;

    public function __construct(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $options): void
    {
        $this->priceManager->flush();
        $this->postFlush($options[self::ENTITY], $options);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(array $listOfOptions): void
    {
        $this->priceManager->flush();
        foreach ($listOfOptions as $options) {
            $this->postFlush($options[self::ENTITY], $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteWithoutFlush($entity, array $options): void
    {
        /** @var ProductPrice $entity */

        $this->priceManager->remove($entity);
    }
}
