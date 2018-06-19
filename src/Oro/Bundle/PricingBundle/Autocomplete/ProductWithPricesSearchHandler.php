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
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;

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
     * @param string $className
     * @param ProductSearchRepository $productSearchRepository
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param ManagerRegistry $registry
     * @param ProductPriceFormatter $productPriceFormatter
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function __construct(
        $className,
        ProductSearchRepository $productSearchRepository,
        PriceListRequestHandler $priceListRequestHandler,
        ManagerRegistry $registry,
        ProductPriceFormatter $productPriceFormatter,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->className = $className;
        $this->productSearchRepository = $productSearchRepository;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->registry = $registry;
        $this->productPriceFormatter = $productPriceFormatter;
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

        $products = $this->findProducts($query, $page, $perPage);

        if (empty($products)) {
            return ['results' => [], 'more' => false];
        }

        $items = $this->buildItemsArray($products, $this->findPrices($this->getProductIds($products)));

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
            $result['defaultName.string'] = $product->getName()->getString();
            $result['prices'] = [];
            $result['units'] = $product->getAvailableUnitsPrecision();

            /** @var ProductPrice $price */
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
     * @param int[] $productIds
     * @return ProductPrice[]
     */
    private function findPrices(array $productIds)
    {
        if (count($productIds) > 0) {
            // TODO: BB-14587 replace with price provider
            $prices = $this->getProductPriceRepository()
                ->getFindByPriceListIdAndProductIdsQueryBuilder(
                    $this->priceListRequestHandler->getPriceListByCustomer()->getId(),
                    $productIds,
                    true,
                    $this->userCurrencyManager->getUserCurrency()
                )
                ->getQuery()
                ->getResult();

            return $prices;
        }

        return [];
    }

    /**
     * @param array $products
     * @return array
     */
    private function getProductIds(array &$products)
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->getId();
        }

        return $ids;
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return Product[]
     */
    private function findProducts($search, $firstResult, $maxResults)
    {
        $foundItems = $this->productSearchRepository->findBySkuOrName($search, $firstResult-1, $maxResults);
        $ids = [];

        foreach ($foundItems as $foundItem) {
            $ids[] = $foundItem->getSelectedData()['product_id'];
        }

        if (empty($ids)) {
            return [];
        }

        return $this->getProductRepository()->getProductsByIds($ids);
    }

    /**
     * @param Product[] $products
     * @param ProductPrice[] $prices
     * @return array
     */
    private function buildItemsArray($products, $prices)
    {
        $items = [];

        foreach ($products as $product) {
            $item['product'] = $product;
            $item['prices'] = [];

            foreach ($prices as $price) {
                if ($price->getProduct()->getId() === $product->getId()) {
                    $item['prices'][] = $price;
                }
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

    /**
     * @return CombinedProductPriceRepository
     */
    private function getProductPriceRepository()
    {
        return $this->registry->getRepository(CombinedProductPrice::class);
    }
}
