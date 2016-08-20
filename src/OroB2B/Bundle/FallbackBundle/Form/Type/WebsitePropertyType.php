<?php

namespace Oro\Bundle\FallbackBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;

class WebsitePropertyType extends AbstractType
{
    const NAME = 'orob2b_fallback_website_property';

    const FIELD_DEFAULT  = 'default';
    const FIELD_WEBSITES = 'websites';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
                array_merge($formOptions, ['label' => 'oro.fallback.value.default'])
            )
            ->add(self::FIELD_WEBSITES, WebsiteCollectionType::NAME, ['type' => $formType, 'options' => $formOptions]);

        $builder->addViewTransformer(new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_WEBSITES));
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
