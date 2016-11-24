<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeNameFiller;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContentNodeType extends AbstractType
{
    const NAME = 'oro_web_catalog_content_node';

    /**
     * @var ContentNodeNameFiller
     */
    private $nameFiller;

    /**
     * @param ContentNodeNameFiller $nameFiller
     */
    public function __construct(ContentNodeNameFiller $nameFiller)
    {
        $this->nameFiller = $nameFiller;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                    'required' => false
                ]
            )
            ->add(
                'parentScopeUsed',
                CheckboxType::class,
                [
                    'label' => 'oro.webcatalog.contentnode.parent_scope_used.label',
                    'required' => false
                ]
            )
            ->add(
                'scopes',
                ScopeCollectionType::NAME,
                [
                    'entry_options' => [
                        'scope_type' => 'web_content'
                    ],
                ]
            )
            ->add(
                'contentVariants',
                ContentVariantCollectionType::NAME,
                [
                    'label' => 'oro.webcatalog.contentvariant.entity_plural_label'
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
            }

            if ($data->getId()) {
                $form->add(
                    'name',
                    TextType::class,
                    [
                        'label' => 'oro.webcatalog.contentnode.name.label',
                        'required' => true,
                        'constraints' => [new NotBlank()]
                    ]
                );
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
            $this->nameFiller->fillName($data);

            if ($data->isParentScopeUsed()) {
                $data->resetScopes();
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
