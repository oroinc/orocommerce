<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class DashesSequenceType extends AbstractType
{
    const NAME = 'oro_dashes_sequence';

    public function getParent()
    {
        return IntegerType::class;
    }

    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
