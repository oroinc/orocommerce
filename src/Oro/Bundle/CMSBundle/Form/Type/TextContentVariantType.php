<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CMS block content variant form type
 */
class TextContentVariantType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'default',
                RadioType::class,
                [
                    'label' => 'oro.cms.page.default.label',
                    'required' => false
                ]
            )
            ->add(
                'content',
                WYSIWYGType::class,
                [
                    'label' => 'oro.cms.page.content.label',
                    'auto_render' => false,
                    'required' => false,
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::class,
                [
                    'label' => 'oro.cms.contentblock.scopes.label',
                    'entry_options' => [
                        'scope_type' => 'cms_content_block'
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => TextContentVariant::class,
                'error_mapping' => ['contentStyle' => 'content_style'],
            ]
        );
    }
}
