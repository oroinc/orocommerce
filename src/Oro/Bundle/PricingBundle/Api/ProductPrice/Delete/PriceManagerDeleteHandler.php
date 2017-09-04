<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Delete;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class PriceManagerDeleteHandler extends DeleteHandler
{
    /**
     * @var PriceManager
     */
    private $priceManager;

    /**
     * @param PriceManager $priceManager
     */
    public function __construct(PriceManager $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    /**
     * @inheritDoc
     */
    protected function deleteEntity($entity, ObjectManager $em)
    {
        if (!$entity instanceof ProductPrice) {
            return;
        }

        $this->priceManager->remove($entity);
        $this->priceManager->flush();
    }
}
