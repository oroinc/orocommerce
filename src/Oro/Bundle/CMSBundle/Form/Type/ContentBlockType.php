<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\ContentBlock\DefaultContentVariantScopesResolver;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Content block form type
 */
class ContentBlockType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_cms_content_block';

    /**
     * @var DefaultContentVariantScopesResolver
     */
    private $defaultVariantScopesResolver;

    public function __construct(DefaultContentVariantScopesResolver $resolver)
    {
        $this->defaultVariantScopesResolver = $resolver;
    }

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
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.cms.contentblock.titles.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]]
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
                ScopeCollectionType::class,
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

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $contentBlock = $event->getData();
                if ($contentBlock instanceof ContentBlock) {
                    $this->defaultVariantScopesResolver->resolve($contentBlock);
                }
            }
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
