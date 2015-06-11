<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

class CurrencyProvider
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
     * @return array
     */
    public function getAvailableCurrencies()
    {
        return $this->getRepository()->getAvailableCurrencies();
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->registry->getRepository($this->className);
    }
}
