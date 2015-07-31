<?php

namespace OroB2B\Bundle\AttributeBundle\Form\Type;

use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackValueType;
use OroB2B\Bundle\FallbackBundle\Form\Type\FallbackPropertyType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HiddenFallbackValueType extends AbstractType
{
    const NAME = 'orob2b_attribute_hidden_fallback';
    const EXTEND_VALUE = 'extend_value';
    const FALLBACK_VALUE = 'fallback_value';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::EXTEND_VALUE, $options['extend_value_type'])
            ->add(
                self::FALLBACK_VALUE,
                FallbackValueType::NAME,
                [
                    'type' => $options['type'],
                    'options' => $options['options'],
                    'fallback_type' => $options['fallback_type'],
                    'fallback_type_locale' => $options['fallback_type_locale'],
                    'fallback_type_parent_locale' => $options['fallback_type_parent_locale'],
                    'enabled_fallbacks' => $options['enabled_fallbacks'],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'type',
        ]);

        $resolver->setDefaults([
            'data_class' => null,
            'options' => [],
            'fallback_type' => FallbackPropertyType::NAME,
            'fallback_type_locale' => null,
            'fallback_type_parent_locale' => null,
            'enabled_fallbacks' => [],
            'extend_value_type' => 'hidden',
            'default_callback' => function (FallbackType $fallbackType) {
                return [self::FALLBACK_VALUE => $fallbackType];
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['extend_value_type'] = $options['extend_value_type'];
    }
}
