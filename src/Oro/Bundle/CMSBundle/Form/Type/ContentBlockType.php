<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;

class ContentBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'alias',
                TextType::class,
                [
                    'label' => 'oro.cms.contentblock.alias.label',
                    'required' => true
                ]
            )
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.cms.contentblock.titles.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]]
                ]
            )
            ->add(
                'enabled',
                CheckboxType::class,
                [
                    'label' => 'oro.cms.contentblock.enabled.label',
                    'required' => false
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'label' => 'oro.cms.contentblock.scopes.label',
                    'entry_options' => [
                        'scope_type' => 'cms_content_block'
                    ],
                ]
            )->add(
                'contentVariants',
                TextContentVariantCollectionType::class,
                [
                    'label' => 'oro.cms.contentblock.content_variants.label',
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
                'data_class' => ContentBlock::class
            ]
        );
    }
}
