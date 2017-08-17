<?php

namespace Oro\Bundle\PromotionBundle\Entity\GeneratorExtensions;

use CG\Generator\PhpClass;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;

/**
 * Add AppliedDiscountsAwareInterface and AppliedCouponsAwareInterface interfaces to Order
 */
class ExtendOrderGeneratorExtension extends AbstractEntityGeneratorExtension
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
        $class->addInterfaceName(AppliedDiscountsAwareInterface::class);
        $class->addInterfaceName(AppliedCouponsAwareInterface::class);
    }
}
