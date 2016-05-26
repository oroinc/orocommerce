<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FrontendProductPricesProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param UserCurrencyProvider $userCurrencyProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListRequestHandler $priceListRequestHandler,
        UserCurrencyProvider $userCurrencyProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->userCurrencyProvider = $userCurrencyProvider;
    }

    /**
     * @param ContextInterface $context
     * @return ProductPrice[]
     */
    public function getData(ContextInterface $context)
    {
        /** @var Product $product */
        $product = $context->data()->get('product');
        $productId = $product->getId();

        if (!array_key_exists($productId, $this->data)) {
            $priceList = $this->priceListRequestHandler->getPriceListByAccount();

            /** @var ProductPriceRepository $priceRepository */
            $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:CombinedProductPrice');

            $this->data[$productId] = $priceRepository->findByPriceListIdAndProductIds(
                $priceList->getId(),
                [$productId],
                true,
                $this->userCurrencyProvider->getUserCurrency(),
                null,
                [
                    'unit' => 'ASC',
                    'currency' => 'DESC',
                    'quantity' => 'ASC',
                ]
            );
        }

        return $this->data[$productId];
    }
}
