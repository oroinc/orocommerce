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

        $this->setProductsPrices([$product->getId()]);

        return $this->data[$product->getId()];
    }

    public function getProductsPrices($products)
    {
        $productsId = [];
        foreach ($products as $product) {
            $productsId[] = $product->getId();
        }

        $this->setProductsPrices($productsId);
        $productsPrices = [];

        foreach ($productsId as $productId) {
            if ($this->data[$productId]) {
                $productsPrices[$productId] = $this->data[$productId];
            }
        }

        return $productsPrices;
    }

    protected function setProductsPrices($productsId)
    {
        $productsId = array_filter($productsId, function ($productId) {
            return !array_key_exists($productId, $this->data);
        });
        if (!$productsId) {
            return;
        }

        $priceList = $this->priceListRequestHandler->getPriceListByAccount();

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:CombinedProductPrice');
        $prices = $priceRepository->findByPriceListIdAndProductIds(
            $priceList->getId(),
            $productsId,
            true,
            $this->userCurrencyManager->getUserCurrency(),
            null,
            [
                'unit' => 'ASC',
                'currency' => 'DESC',
                'quantity' => 'ASC',
            ]
        );

        $pricesByProduct = [];
        $productUnits = [];
        foreach ($prices as $price) {
            $product = $price->getProduct();
            $productId = $product->getId();

            if (!isset($productUnits[$productId])) {
                $productUnits[$productId] = [];
                foreach ($product->getUnitPrecisions() as $unitPrecision) {
                    if ($unitPrecision->isSell()) {
                        $productUnits[$productId][] = $unitPrecision->getUnit();
                    }
                }
            }

            if (in_array($price->getUnit(), $productUnits[$productId])) {
                $pricesByProduct[$productId][] = $price;
            }
        }

        foreach ($productsId as $productId) {
            $this->data[$productId] = isset($pricesByProduct[$productId]) ? $pricesByProduct[$productId] : [];
        }
    }
}
