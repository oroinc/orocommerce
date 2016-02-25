<?php

namespace OroB2B\Bundle\PricingBundle\Layout\Extension\Provider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FrontendProductPricesDataProvider implements DataProviderInterface
{
    /** @var array */
    protected $data;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PriceListRequestHandler */
    protected $priceListRequestHandler;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Product $product */
        $product = $context->data()->get('product');
        $productId = $product->getId();

        if (!$this->data[$productId]) {
            $priceList = $this->priceListRequestHandler->getPriceListByAccount();

            /** @var ProductPriceRepository $priceRepository */
            $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:CombinedProductPrice');

            $this->data[$productId] = $priceRepository->findByPriceListIdAndProductIds(
                $priceList->getId(),
                [$productId]
            );
        }

        return $this->data[$productId];
    }
}
