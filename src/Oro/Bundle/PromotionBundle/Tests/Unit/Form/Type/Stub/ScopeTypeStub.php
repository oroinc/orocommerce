<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\ScopeStub;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeTypeStub extends AbstractType
{
    public const NAME = 'oro_scope';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('locale', TextType::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', ScopeStub::class);
        $resolver->setDefault('scope_type', 'test');
        $resolver->setDefault('scope_fields', []);
        $resolver->setDefault('constraints', []);
    }

    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return $this->getName();
    }
}
