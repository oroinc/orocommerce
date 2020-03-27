<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SluggableEntityFormStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titles', LocalizedFallbackValueCollectionType::class, ['required' => true])
            ->add(
                'slugPrototypes',
                LocalizedSlugType::class,
                [
                    'required' => false,
                    'source_field' => $options['source_field'] ?? 'titles',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('source_field');
    }
}
