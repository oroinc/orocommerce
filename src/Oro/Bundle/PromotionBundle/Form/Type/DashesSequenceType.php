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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return IntegerType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
