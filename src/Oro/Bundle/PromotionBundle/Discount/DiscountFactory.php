<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedDiscountException;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedTypeException;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates discount instances from discount configuration.
 *
 * Factory that instantiates appropriate discount objects based on type,
 * configures them with provided options, and associates them with promotions.
 * Supports extensibility through type-to-service mapping.
 */
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

    public function create(
        DiscountConfiguration $configuration,
        ?PromotionDataInterface $promotion = null
    ): DiscountInterface {
        $type = $configuration->getType();
        if (!array_key_exists($type, $this->typeToServiceMap)) {
            throw new UnsupportedTypeException(sprintf('Unknown discount type %s', $type));
        }

        $discount = $this->container->get($this->typeToServiceMap[$type]);
        if (!$discount instanceof DiscountInterface) {
            throw new UnsupportedDiscountException(
                sprintf('Discount "%s" should implement DiscountInterface.', get_class($discount))
            );
        }
        $discount->configure($configuration->getOptions());

        if ($promotion) {
            $discount->setPromotion($promotion);
        }

        return $discount;
    }
}
