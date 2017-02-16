<?php

namespace Oro\Bundle\CatalogBundle\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;

class CategoryType extends AbstractType
{
    const NAME = 'oro_catalog_category';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'parentCategory',
                EntityIdentifierType::NAME,
                ['class' => $this->dataClass, 'multiple' => false]
            )
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.catalog.category.titles.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.catalog.category.short_descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                            'width' => 500,
                            'height' => 200,
                        ],
                    ],
                ]
            )
            ->add(
                'longDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.catalog.category.long_descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                            'width' => 500,
                            'height' => 200,
                        ],
                    ],
                ]
            )
            ->add(
                'appendProducts',
                EntityIdentifierType::NAME,
                [
                    'class'    => $this->productClass,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'removeProducts',
                EntityIdentifierType::NAME,
                [
                    'class'    => $this->productClass,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true,
                ]
            )
            ->add(
                'smallImage',
                'oro_image',
                [
                    'label'    => 'oro.catalog.category.small_image.label',
                    'required' => false
                ]
            )
            ->add(
                'largeImage',
                'oro_image',
                [
                    'label'    => 'oro.catalog.category.large_image.label',
                    'required' => false
                ]
            )
            ->add(
                'defaultProductOptions',
                CategoryDefaultProductOptionsType::NAME,
                [
                    'required' => false
                ]
            )
            ->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::NAME,
                [
                    'label'    => 'oro.catalog.category.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'titles'
                ]
            )
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetDataListener']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetDataListener(FormEvent $event)
    {
        $category = $event->getData();

        if ($category instanceof Category && $category->getId()) {
            $url = $this->urlGenerator->generate(
                'oro_catalog_category_get_changed_slugs',
                ['id' => $category->getId()]
            );

            $event->getForm()->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::NAME,
                [
                    'label'    => 'oro.catalog.category.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'names',
                    'get_changed_slugs_url' => $url
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'category',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

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
}
