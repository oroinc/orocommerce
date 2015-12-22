<?php

namespace OroB2B\Bundle\PricingBundle\Layout\Extension\Provider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FrontendProductPricesDataProvicer implements DataProviderInterface
{
    const PRODUCT_ID_ALIAS = 'productId';

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
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$context->has(self::PRODUCT_ID_ALIAS)) {
            throw new \RuntimeException(sprintf("Context[%s] should be specified.", self::PRODUCT_ID_ALIAS));
        }
        $productId = $context->get(self::PRODUCT_ID_ALIAS);

        if (!$this->data[$productId]) {
            /** @var Product $product */
            $product = $this->doctrineHelper->getEntityReference('OroB2BProductBundle:Product', $productId);
            $priceList = $this->frontendPriceListRequestHandler->getPriceList();

            /** @var ProductPriceRepository $priceRepository */
            $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:ProductPrice');

            $this->data[$productId] = $priceRepository->findByPriceListIdAndProductIds(
                $priceList->getId(),
                [$product->getId()]
            );
        }

        return $this->data[$productId];
    }
}
