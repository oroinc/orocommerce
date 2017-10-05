<?php

namespace Oro\Bundle\PromotionBundle\Entity\GeneratorExtension;

use CG\Generator\PhpClass;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;

/**
 * Add AppliedDiscountsAwareInterface and AppliedCouponsAwareInterface interfaces to Entities that has relation to
 * Applied Coupons and Applied Discounts.
 */
class PromotionAwareEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    /**
     * @var array
     */
    private $supportedEntities = [];

    /**
     * @param string $entityClassName
     */
    public function registerSupportedEntity($entityClassName)
    {
        $this->supportedEntities[$entityClassName] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $schema): bool
    {
        return !empty($this->supportedEntities[$schema['class']]);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $schema, PhpClass $class)
    {
        if ($class->hasProperty('appliedPromotions')) {
            $class->addInterfaceName(AppliedPromotionsAwareInterface::class);
        }
        if ($class->hasProperty('appliedCoupons')) {
            $class->addInterfaceName(AppliedCouponsAwareInterface::class);
        }
    }
}
