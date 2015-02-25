<?php

namespace OroB2B\Bundle\FallbackBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\FallbackBundle\Form\DataTransformer\MultipleValueTransformer;

class LocalizedPropertyType extends AbstractType
{
    const NAME = 'orob2b_fallback_localized_property';

    const FIELD_DEFAULT = 'default';
    const FIELD_LOCALES = 'locales';

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
        $formType    = $options['type'];
        $formOptions = $options['options'];

        $builder
            ->add(
                self::FIELD_DEFAULT,
                $formType,
                array_merge($formOptions, ['label' => 'orob2b.fallback.value.default'])
            )
            ->add(self::FIELD_LOCALES, LocaleCollectionType::NAME, ['type' => $formType, 'options' => $formOptions]);

        $builder->addViewTransformer(new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_LOCALES));
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
            'options' => [],
        ]);
    }
}
