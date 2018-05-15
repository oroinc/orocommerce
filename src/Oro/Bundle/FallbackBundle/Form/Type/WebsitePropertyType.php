<?php

namespace Oro\Bundle\FallbackBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\MultipleValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebsitePropertyType extends AbstractType
{
    const NAME = 'oro_fallback_website_property';

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
        $formType    = $options['entry_type'];
        $formOptions = $options['entry_options'];

        $builder
            ->add(
                self::FIELD_DEFAULT,
                $formType,
                array_merge($formOptions, ['label' => 'oro.fallback.value.default'])
            )
            ->add(self::FIELD_WEBSITES, WebsiteCollectionType::class, [
                'entry_type' => $formType, 'entry_options' => $formOptions
            ]);

        $builder->addViewTransformer(new MultipleValueTransformer(self::FIELD_DEFAULT, self::FIELD_WEBSITES));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entry_type',
        ]);

        $resolver->setDefaults([
            'entry_options' => [],
        ]);
    }
}
