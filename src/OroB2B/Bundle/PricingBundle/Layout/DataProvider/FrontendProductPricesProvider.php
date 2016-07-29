<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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
            $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:CombinedProductPrice');
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
