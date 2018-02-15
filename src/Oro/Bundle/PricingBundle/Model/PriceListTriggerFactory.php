<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListTrigger;
use Oro\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceListTriggerFactory
{
    const PRICE_LIST = 'priceList';
    const PRODUCT = 'product';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param PriceList $priceList
     * @param array|int[] $productIds
     * @return PriceListTrigger
     */
    public function create(PriceList $priceList, array $productIds = [])
    {
        return new PriceListTrigger($priceList, $productIds);
    }

    /**
     * @param int $priceListId
     * @param array|int[] $productIds
     * @return array
     */
    public function createFromIds($priceListId, array $productIds)
    {
        return [
            self::PRICE_LIST => $priceListId,
            self::PRODUCT => $this->getProductIds($productIds)
        ];
    }

    /**
     * @param PriceListTrigger $trigger
     * @return array
     */
    public function triggerToArray(PriceListTrigger $trigger)
    {
        return [
            self::PRICE_LIST => $trigger->getPriceList()->getId(),
            self::PRODUCT => $this->getProductIds($trigger->getProducts())
        ];
    }

    /**
     * @param array|null $data
     * @return PriceListTrigger
     */
    public function createFromArray($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }

        $priceList = $this->getPriceList($data);
        if (!$priceList) {
            throw new InvalidArgumentException('Price List is required.');
        }

        return $this->create($priceList, $this->getProducts($data));
    }

    /**
     * @param array $data
     * @return null|PriceList
     */
    protected function getPriceList(array $data)
    {
        if (empty($data[self::PRICE_LIST])) {
            return null;
        }

        return $this->registry
            ->getManagerForClass(PriceList::class)
            ->find(PriceList::class, $data[self::PRICE_LIST]);
    }

    /**
     * @param array $data
     * @return array|int[]
     */
    protected function getProducts(array $data)
    {
        return $this->getProductIds($data[self::PRODUCT] ?? []);
    }

    /**
     * @param array $products
     * @return array|int[]
     */
    private function getProductIds(array $products)
    {
        return array_map(
            function ($product) {
                return $product instanceof Product ? $product->getId() : $product;
            },
            $products
        );
    }
}
