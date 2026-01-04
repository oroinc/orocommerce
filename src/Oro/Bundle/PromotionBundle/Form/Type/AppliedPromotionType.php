<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for AppliedPromotion
 */
class AppliedPromotionType extends AbstractType
{
    public const NAME = 'oro_promotion_applied_promotion';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('active', HiddenType::class);
        $builder->add('removed', HiddenType::class, ['empty_data' => '0']);
        $builder->add('sourcePromotionId', HiddenType::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AppliedPromotion::class,
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
