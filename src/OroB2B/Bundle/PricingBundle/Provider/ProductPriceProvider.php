<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

class ProductPriceProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param ManagerRegistry $registry
     * @param string $className
     */
    public function __construct(ManagerRegistry $registry, $className)
    {
        $this->registry = $registry;
        $this->className = $className;
    }

    /**
     * @param int $priceListId
     * @param array $productIds
     * @param string|null $currency
     * @return array
     */
    public function getPriceByPriceListIdAndProductIds($priceListId, array $productIds, $currency = null)
    {
        $result = [];
        $prices = $this->getRepository()->findByPriceListIdAndProductIds($priceListId, $productIds, true, $currency);

        if ($prices) {
            foreach ($prices as $price) {
                $result[$price->getProduct()->getId()][$price->getUnit()->getCode()][] = [
                    'price' => $price->getPrice()->getValue(),
                    'currency' => $price->getPrice()->getCurrency(),
                    'qty' => $price->getQuantity()
                ];
            }
        }

        return $result;
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass($this->className)
            ->getRepository($this->className);
    }
}
