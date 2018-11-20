<?php

namespace Oro\Bundle\PricingBundle\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;

/**
 * Class helps to prepare products search result for quick order form
 */
class ProductWithPricesSearchHandler implements SearchHandlerInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var ManagerRegistry $registry
     */
    private $registry;

    /**
     * @var PriceListRequestHandler
     */
    private $priceListRequestHandler;

    /**
     * @var ProductSearchRepository
     */
    private $productSearchRepository;

    /**
     * @var ProductPriceFormatter
     */
    private $productPriceFormatter;

    /**
     * @var UserCurrencyManager
     */
    private $userCurrencyManager;

    /**
     * @param string                  $className
     * @param ProductSearchRepository $productSearchRepository
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param ManagerRegistry         $registry
     * @param ProductPriceFormatter   $productPriceFormatter
     */
    public function __construct(
        $className,
        ProductSearchRepository $productSearchRepository,
        PriceListRequestHandler $priceListRequestHandler,
        ManagerRegistry $registry,
        ProductPriceFormatter $productPriceFormatter
    ) {
        $this->className = $className;
        $this->productSearchRepository = $productSearchRepository;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->registry = $registry;
        $this->productPriceFormatter = $productPriceFormatter;
    }

    /**
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function setUserCurrencyManager(UserCurrencyManager $userCurrencyManager)
    {
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $page = (int)$page > 0 ? (int)$page : 1;
        $perPage = (int)$perPage > 0 ? (int)$perPage : 10;
        $perPage++;

        $foundProducts = $this->searchProducts($query, $page, $perPage);

        $hasMore = count($foundProducts) === $perPage;
        if ($hasMore) {
            $foundProducts = array_slice($foundProducts, 0, $perPage - 1);
        }

        return [
            'results' => array_map([$this, 'convertItem'], $foundProducts),
            'more' => $hasMore,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        if (!isset($item['product_id'], $item['sku'], $item['name'], $item['prices'], $item['product_units'])) {
            return [];
        }

        $prices = [];

        /** @var ProductPrice $price */
        foreach ($item['prices'] as $price) {
            $prices[$price->getUnit()->getCode()][] = $this->productPriceFormatter->formatProductPrice($price);
        }

        return [
            'id' => $item['product_id'],
            'sku' => $item['sku'],
            'defaultName.string' => $item['name'],
            'prices' => $prices,
            'units' => unserialize($item['product_units']),
        ];
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->className;
    }

    /**
     * @param int[] $productIds
     * @return ProductPrice[]
     */
    private function findPrices(array $productIds)
    {
        if (!$productIds) {
            return [];
        }

        if (null !== $this->userCurrencyManager) {
            $currency = $this->userCurrencyManager->getUserCurrency();
        } else {
            $currency = null;
        }

        return $this->getProductPriceRepository()
            ->getFindByPriceListIdAndProductIdsQueryBuilder(
                $this->priceListRequestHandler->getPriceListByCustomer()->getId(),
                $productIds,
                true,
                $currency
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return array
     */
    private function searchProducts($search, $firstResult, $maxResults): array
    {
        $foundItems = $this->productSearchRepository->getSearchQueryBySkuOrName($search, $firstResult - 1, $maxResults)
            ->addSelect('product_units')
            ->getResult()
            ->getElements();

        if (!$foundItems) {
            return [];
        }

        $foundProducts = [];
        foreach ($foundItems as $foundItem) {
            $selectedData = $foundItem->getSelectedData();
            $foundProducts[$selectedData['product_id']] = $selectedData + ['prices' => []];
        }

        $prices = $this->findPrices(array_column($foundProducts, 'product_id'));
        foreach ($prices as $productPrice) {
            $foundProducts[$productPrice->getProduct()->getId()]['prices'][] = $productPrice;
        }

        return array_values($foundProducts);
    }

    /**
     * @return CombinedProductPriceRepository
     */
    private function getProductPriceRepository()
    {
        return $this->registry->getRepository(CombinedProductPrice::class);
    }
}
