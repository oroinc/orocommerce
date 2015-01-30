<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\FallbackValueTransformer;

class FallbackValueType extends AbstractType
{
    const NAME = 'orob2b_attribute_fallback_value';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired([
            'type',
        ]);

        $resolver->setDefaults([
            'data_class'        => null,
            'options'           => [],
            'fallback_type'     => AttributePropertyFallbackType::NAME,
            'enabled_fallbacks' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'value',
                $options['type'],
                array_merge($options['options'], ['required' => false])
            )
            ->add(
                'fallback',
                $options['fallback_type'],
                ['enabled_fallbacks' => $options['enabled_fallbacks'], 'required' => false]
            );

        $builder->addViewTransformer(new FallbackValueTransformer());
    }
}
