<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * This form type used for customize field widget in easy way instead of customizing it be field name,
 * which is used in actions.yml
 */
class DashesSequenceType extends AbstractType
{
    const NAME = 'oro_promotion_coupon_dashes_sequence';

    #[\Override]
    public function getParent(): ?string
    {
        return IntegerType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
