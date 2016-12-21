<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContentNodeType extends AbstractType
{
    const NAME = 'oro_web_catalog_content_node';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var ContentNode $contentNode */
        $contentNode = array_key_exists('data', $options) ? $options['data'] : null;
        $builder
            ->add(
                'parentNode',
                EntityIdentifierType::NAME,
                ['class' => ContentNode::class, 'multiple' => false]
            )
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.webcatalog.contentnode.titles.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]]
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'entry_options' => [
                        'scope_type' => 'web_content',
                        'web_catalog' => $contentNode ? $contentNode->getWebCatalog() : null
                    ],
                ]
            )
            ->add(
                'rewriteVariantTitle',
                CheckboxType::class,
                [
                    'label' => 'oro.webcatalog.contentnode.rewrite_variant_title.label',
                    'required' => false
                ]
            )
            ->add(
                'contentVariants',
                ContentVariantCollectionType::NAME,
                [
                    'label' => 'oro.webcatalog.contentvariant.entity_plural_label',
                    'entry_options' => [
                        'web_catalog' => $contentNode ? $contentNode->getWebCatalog() : null
                    ]
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if ($data instanceof ContentNode) {
            $form = $event->getForm();

            if ($data->getParentNode() instanceof ContentNode) {
                $form->add(
                    'slugPrototypes',
                    LocalizedSlugType::NAME,
                    [
                        'label' => 'oro.webcatalog.contentnode.slug_prototypes.label',
                        'required' => true,
                        'options' => ['constraints' => [new NotBlank(), new UrlSafe()]],
                        'source_field' => 'titles'
                    ]
                );
                $form->add(
                    'parentScopeUsed',
                    CheckboxType::class,
                    [
                        'label' => 'oro.webcatalog.contentnode.parent_scope_used.label',
                        'required' => false
                    ]
                );
            }

            $defaultVariant = $data->getDefaultVariant();
            if ($defaultVariant) {
                $defaultVariant->resetScopes();
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        /** @var ContentNode $contentNode */
        $data = $event->getData();
        if ($data instanceof ContentNode) {
            if ($data->getParentNode()) {
                if ($data->isParentScopeUsed()) {
                    $data->resetScopes();
                }
            } else {
                $data->setParentScopeUsed(false);
            }
            if (!$data->getContentVariants()->isEmpty()) {
                $data->getContentVariants()->map(
                    function (ContentVariant $contentVariant) use ($data) {
                        if (!$contentVariant->getNode()) {
                            $contentVariant->setNode($data);
                        }
                    }
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ContentNode::class,
            ]
        );
    }

    /**
     * @return string
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
}
