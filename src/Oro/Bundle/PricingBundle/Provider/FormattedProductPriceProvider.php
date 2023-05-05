<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides formatted price data for specific products.
 */
class FormattedProductPriceProvider
{
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;
    private ProductPriceProviderInterface $productPriceProvider;
    private ProductPriceFormatter $productPriceFormatter;
    private ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler;
    private UserCurrencyManager $userCurrencyManager;

    public function __construct(
        ManagerRegistry $doctrine,
        AclHelper $aclHelper,
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceFormatter $productPriceFormatter,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
        $this->productPriceProvider = $productPriceProvider;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param int[] $productIds
     *
     * @return array
     *  [
     *      product id => [
     *          'prices' => [product unit code => formatted product price data, ...],
     *          'units' => [product unit code => product unit precision, ...]
     *      ], ...
     *  ]
     */
    public function getFormattedProductPrices(array $productIds): array
    {
        $qb = $this->doctrine->getRepository(Product::class)->getProductsQueryBuilder($productIds);
        $qb->orderBy('p.id');
        /** @var Product[] $products */
        $products = $this->aclHelper->apply($qb)->getResult();
        if (!$products) {
            return [];
        }

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $products,
            [$this->userCurrencyManager->getUserCurrency()]
        );

        $result = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            $result[$productId] = [
                'prices' => $this->buildFormattedProductPrices($productId, $prices),
                'units'  => $product->getSellUnitsPrecision()
            ];
        }

        return $result;
    }

    private function buildFormattedProductPrices(int $productId, array $prices): array
    {
        $result = [];
        $productPrices = $prices[$productId] ?? [];
        /** @var ProductPriceInterface $price */
        foreach ($productPrices as $price) {
            $result[$price->getUnit()->getCode()][] = $this->productPriceFormatter->formatProductPrice($price);
        }

        return $result;
    }
}
