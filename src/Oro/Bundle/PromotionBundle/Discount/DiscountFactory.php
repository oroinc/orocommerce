<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DiscountFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $typeToServiceMap = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $type
     * @param string $serviceName
     */
    public function addType($type, $serviceName)
    {
        $this->typeToServiceMap[$type] = $serviceName;
    }

    /**
     * @param DiscountConfiguration $configuration
     * @return DiscountInterface
     */
    public function create(DiscountConfiguration $configuration): DiscountInterface
    {
        $type = $configuration->getType();
        if (!array_key_exists($type, $this->typeToServiceMap)) {
            throw new UnsupportedTypeException(sprintf('Unknown discount type %s', $type));
        }

        $discount = $this->container->get($this->typeToServiceMap[$type]);
        if (!$discount instanceof DiscountInterface) {
            // TODO: maybe change exception class
            throw new \RuntimeException(sprintf('Unsupported discount object %s', get_class($discount)));
        }
        $discount->configure($configuration->getOptions());

        return $discount;
    }
}
