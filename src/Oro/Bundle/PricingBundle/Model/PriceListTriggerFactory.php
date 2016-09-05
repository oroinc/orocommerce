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
     * @param Product|null $product
     * @return PriceListTrigger
     */
    public function create(PriceList $priceList, Product $product = null)
    {
        return new PriceListTrigger($priceList, $product);
    }

    /**
     * @param PriceListTrigger $trigger
     * @return array
     */
    public function triggerToArray(PriceListTrigger $trigger)
    {
        return [
            self::PRICE_LIST => $trigger->getPriceList()->getId(),
            self::PRODUCT => $trigger->getProduct() ? $trigger->getProduct()->getId() : null
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
        $product = $this->getProduct($data);

        return $this->create($priceList, $product);
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
     * @return null|PriceList
     */
    protected function getProduct(array $data)
    {
        if (empty($data[self::PRODUCT])) {
            return null;
        }

        return $this->registry
            ->getManagerForClass(Product::class)
            ->find(Product::class, $data[self::PRODUCT]);
    }
}
