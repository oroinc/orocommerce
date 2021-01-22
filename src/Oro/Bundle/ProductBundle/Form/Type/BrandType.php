<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form type for Brand entity
 */
class BrandType extends AbstractType
{
    const NAME = 'oro_product_brand';

    /** @var  string */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.brand.names.label',
                    'required' => true,
                    'entry_options' => [
                        'constraints' => [
                            new NotBlank(['message' => 'oro.product.brand.form.update.messages.notBlank'])
                        ]
                    ]
                ]
            )
            ->add(
                'status',
                BrandStatusType::class,
                [
                    'label' => 'oro.product.brand.status.label'
                ]
            )
            ->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label'    => 'oro.product.brand.slugs.label',
                    'required' => false,
                    'source_field' => 'names'
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.brand.descriptions.label',
                    'required' => false,
                    'field' => ['wysiwyg', 'wysiwyg_style', 'wysiwyg_properties'],
                    'entry_type' => WYSIWYGValueType::class,
                    'entry_options' => [
                        'entity_class' => LocalizedFallbackValue::class
                    ],
                    'use_tabs' => true,
                ]
            )
            ->add(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.brand.short_descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'entry_type' => OroRichTextType::class,
                    'entry_options' => [
                        'wysiwyg_options' => [
                            'elementpath' => true,
                            'resize' => true,
                            'height' => 300,
                        ]
                    ],
                    'use_tabs' => true,
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'csrf_token_id' => 'brand',
        ]);
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
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
    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
