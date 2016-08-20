<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class FrontendProductPricesProvider
{
    /**
     * @var array
     */
    protected $productPrices = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListRequestHandler $priceListRequestHandler,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param Product $product
     *
     * @return ProductPrice[]
     */
    public function getProductPrices(Product $product)
    {
        $productId = $product->getId();
        if (!array_key_exists($productId, $this->productPrices)) {
            $priceList = $this->priceListRequestHandler->getPriceListByAccount();

            /** @var ProductPriceRepository $priceRepository */
            $priceRepository = $this->doctrineHelper->getEntityRepository('OroPricingBundle:CombinedProductPrice');
            $prices = $priceRepository->findByPriceListIdAndProductIds(
                $priceList->getId(),
                [$productId],
                true,
                $this->userCurrencyManager->getUserCurrency(),
                null,
                [
                    'unit' => 'ASC',
                    'currency' => 'DESC',
                    'quantity' => 'ASC',
                ]
            );
            if (count($prices)) {
                $unitPrecisions = $product->getUnitPrecisions();

                $unitsToSell = [];
                foreach ($unitPrecisions as $unitPrecision) {
                    $unitsToSell[$unitPrecision->getUnit()->getCode()] = $unitPrecision->isSell();
                }

                $prices = array_filter(
                    $prices,
                    function (CombinedProductPrice $price) use ($unitsToSell) {
                        return !empty($unitsToSell[$price->getProductUnitCode()]);
                    }
                );
            }

            $this->productPrices[$productId] = $prices;
        }

        return $this->productPrices[$productId];
    }
}
