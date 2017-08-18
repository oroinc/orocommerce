<?php

namespace Oro\Bundle\PromotionBundle\Entity\GeneratorExtension;

use CG\Generator\PhpClass;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;

/**
 * Add AppliedDiscountsAwareInterface and AppliedCouponsAwareInterface interfaces to Entities that has relation to
 * Applied Coupons and Applied Discounts.
 */
class PromotionAwareEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return $schema['class'] === Order::class;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        if ($class->hasProperty('appliedDiscounts')) {
            $class->addInterfaceName(AppliedDiscountsAwareInterface::class);
        }
        if ($class->hasProperty('appliedCoupons')) {
            $class->addInterfaceName(AppliedCouponsAwareInterface::class);
        }
    }
}
