<?php
declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Entity\GeneratorExtension;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Add AppliedDiscountsAwareInterface and AppliedCouponsAwareInterface interfaces to an entity that has relation to
 * Applied Coupons and Applied Discounts.
 */
class PromotionAwareEntityGeneratorExtension extends AbstractEntityGeneratorExtension
{
    private array $supportedEntities = [];

    public function registerSupportedEntity(string $entityClassName)
    {
        $this->supportedEntities[$entityClassName] = true;
    }

    public function supports(array $schema): bool
    {
        return !empty($this->supportedEntities[$schema['class']]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function generate(array $schema, ClassGenerator $class): void
    {
        if ($class->hasProperty('appliedPromotions')) {
            $class->addImplement(AppliedPromotionsAwareInterface::class);
        }
        if ($class->hasProperty('appliedCoupons')) {
            $class->addImplement(AppliedCouponsAwareInterface::class);
        }
    }
}
