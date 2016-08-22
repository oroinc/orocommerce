<?php

namespace OroB2B\Bundle\PricingBundle\Model\DTO;

use Doctrine\Common\Persistence\ManagerRegistry;
use OroB2B\Bundle\PricingBundle\Model\Exception\InvalidArgumentException;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceRuleTriggerFactory
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
     * @return PriceRuleTrigger
     */
    public function create(PriceList $priceList, Product $product = null)
    {
        return new PriceRuleTrigger($priceList, $product);
    }

    /**
     * @param PriceRuleTrigger $trigger
     * @return array
     */
    public function triggerToArray(PriceRuleTrigger $trigger)
    {
        return [
            self::PRICE_LIST => $trigger->getPriceList()->getId(),
            self::PRODUCT => $trigger->getProduct() ? $trigger->getProduct()->getId() : null
        ];
    }

    /**
     * @param array|null $data
     * @return PriceRuleTrigger
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
