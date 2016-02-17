<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractDataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FrontendProductPricesProvider extends AbstractDataProvider
{
    /** @var array */
    protected $data;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var FrontendPriceListRequestHandler */
    protected $frontendPriceListRequestHandler;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param FrontendPriceListRequestHandler $frontendPriceListRequestHandler
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        FrontendPriceListRequestHandler $frontendPriceListRequestHandler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->frontendPriceListRequestHandler = $frontendPriceListRequestHandler;
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
            $priceList = $this->frontendPriceListRequestHandler->getPriceList();

            /** @var ProductPriceRepository $priceRepository */
            $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:ProductPrice');

            $this->data[$productId] = $priceRepository->findByPriceListIdAndProductIds(
                $priceList->getId(),
                [$productId]
            );
        }

        return $this->data[$productId];
    }
}
