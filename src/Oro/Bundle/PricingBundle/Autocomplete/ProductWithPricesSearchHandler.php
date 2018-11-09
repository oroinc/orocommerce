<?php

namespace Oro\Bundle\PricingBundle\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\SearchBundle\Query\Result\Item;

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
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    private $scopeCriteriaRequestHandler;

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
     * @var ProductPriceProviderInterface
     */
    private $productPriceProvider;

    /**
     * @param string $className
     * @param ProductSearchRepository $productSearchRepository
     * @param ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
     * @param ManagerRegistry $registry
     * @param ProductPriceFormatter $productPriceFormatter
     * @param UserCurrencyManager $userCurrencyManager
     * @param ProductPriceProviderInterface $productPriceProvider
     */
    public function __construct(
        $className,
        ProductSearchRepository $productSearchRepository,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        ManagerRegistry $registry,
        ProductPriceFormatter $productPriceFormatter,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceProviderInterface $productPriceProvider
    ) {
        $this->className = $className;
        $this->productSearchRepository = $productSearchRepository;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->registry = $registry;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productPriceProvider = $productPriceProvider;
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

        $searchResultData = $this->getSearchResultsData($query, $page, $perPage);
        if (empty($searchResultData)) {
            return ['results' => [], 'more' => false];
        }

        $products = $this->getProductRepository()->getProductsByIds(array_keys($searchResultData));
        $items = $this->buildItemsArray(
            $products,
            $this->findPrices($products),
            $searchResultData
        );

        $hasMore = count($items) === $perPage;
        if ($hasMore) {
            $items = array_slice($items, 0, $perPage - 1);
        }

        $result = [];

        foreach ($items as $item) {
            $result[] = $this->convertItem($item);
        }

        return [
            'results' => $result,
            'more'    => $hasMore
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];
        $product = $item['product'];

        if ($product instanceof Product) {
            $result['id'] = $product->getId();
            $result['sku'] = $product->getSku();
            $result['defaultName.string'] = $item['name'];
            $result['prices'] = [];
            $result['units'] = $product->getSellUnitsPrecision();

            /** @var ProductPriceInterface $price */
            foreach ($item['prices'] as $price) {
                $unit = $price->getUnit()->getCode();
                if (!isset($result['prices'][$unit])) {
                    $result['prices'][$unit] = [];
                }

                $result['prices'][$unit][] = $this->productPriceFormatter->formatProductPrice($price);
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->className;
    }

    /**
     * @param array|Product[] $products
     * @return array[]
     */
    private function findPrices(array $products)
    {
        if (\count($products) > 0) {
            $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
                $this->scopeCriteriaRequestHandler->getPriceScopeCriteria(),
                $products,
                [$this->userCurrencyManager->getUserCurrency()]
            );

            return $prices;
        }

        return [];
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     *
     * @return array
     *
     * [
     *     'product.id' => [
     *         product_id => 'id'
     *         name => 'name',
     *         sku => 'sku'
     *      ],
     *      ...
     * ]
     */
    private function getSearchResultsData($search, $firstResult, $maxResults) : array
    {
        $foundItems = $this->productSearchRepository
            ->getSearchQueryBySkuOrName($search, $firstResult-1, $maxResults)
            ->getResult()
            ->getElements();

        return array_combine(
            array_map(function (Item $foundItem) {
                return $foundItem->getSelectedData()['product_id'];
            }, $foundItems),
            array_map(function (Item $foundItem) {
                return $foundItem->getSelectedData();
            }, $foundItems)
        );
    }

    /**
     * @param Product[] $products
     * @param array[] $prices
     * @param array[] $searchResultData
     * @return array
     */
    private function buildItemsArray($products, array $prices, array $searchResultData)
    {
        $items = [];

        foreach ($products as $product) {
            $item['product'] = $product;
            $item['prices'] = [];
            $item['name'] = $searchResultData[$product->getId()]['name'];

            if (!empty($prices[$product->getId()])) {
                $item['prices'] = $prices[$product->getId()];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return ProductRepository
     */
    private function getProductRepository()
    {
        return $this->registry->getRepository(Product::class);
    }
}
