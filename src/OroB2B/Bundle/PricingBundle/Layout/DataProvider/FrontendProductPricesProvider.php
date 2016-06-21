<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
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
                $unitPrecisions = current($prices)->getProduct()->getUnitPrecisions();

                $unitsToSell = [];
                foreach ($unitPrecisions as $unitPrecision) {
                    if ($unitPrecision->isSell()) {
                        $unitsToSell[] = $unitPrecision->getUnit();
                    }
                }

                foreach ($prices as $key => $combinedProductPrice) {
                    if (!in_array($combinedProductPrice->getUnit(), $unitsToSell)) {
                        unset($prices[$key]);
                    }
                }
            }

            $this->data[$productId] = $prices;
        }

        return $this->data[$productId];
    }
}
