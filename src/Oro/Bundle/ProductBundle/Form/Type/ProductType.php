<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGValueType;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FrontendBundle\Form\DataTransformer\PageTemplateEntityFieldFallbackValueTransformer;
use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form type for Product entity
 */
class ProductType extends AbstractType
{
    const NAME = 'oro_product';
    const PAGE_TEMPLATE_ROUTE_NAME = 'oro_product_frontend_product_view';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    private $provider;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var ProductImageHelper
     */
    private $productImageHelper;

    public function __construct(
        DefaultProductUnitProviderInterface $provider,
        UrlGeneratorInterface $urlGenerator,
        ProductImageHelper $productImageHelper
    ) {
        $this->provider = $provider;
        $this->urlGenerator = $urlGenerator;
        $this->productImageHelper = $productImageHelper;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku', TextType::class, ['required' => true, 'label' => 'oro.product.sku.label'])
            ->add('status', ProductStatusType::class, ['label' => 'oro.product.status.label'])
            ->add('brand', BrandSelectType::class, ['label' => 'oro.product.brand.label'])
            ->add(
                'inventory_status',
                EnumSelectType::class,
                [
                    'label'     => 'oro.product.inventory_status.label',
                    'enum_code' => 'prod_inventory_status',
                    'configs'   => ['allowClear' => false]
                ]
            )
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.names.label',
                    'required' => true,
                    'value_class' => ProductName::class,
                    'entry_options' => [
                        'constraints' => [new NotBlank(['message' => 'oro.product.names.blank'])],
                        StripTagsExtension::OPTION_NAME => true,
                    ],
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.descriptions.label',
                    'required' => false,
                    'value_class' => ProductDescription::class,
                    'field' => ['wysiwyg', 'wysiwyg_style', 'wysiwyg_properties'],
                    'entry_type' => WYSIWYGValueType::class,
                    'entry_options' => [
                        'entity_class' => ProductDescription::class,
                        'error_mapping' => ['wysiwygStyle' => 'wysiwyg_style'],
                    ],
                    'use_tabs' => true,
                ]
            )
            ->add(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.product.short_descriptions.label',
                    'required' => false,
                    'value_class' => ProductShortDescription::class,
                    'field' => 'text',
                    'entry_type' => OroRichTextType::class,
                    'entry_options' => [
                        'wysiwyg_options' => [
                            'autoRender' => false,
                            'elementpath' => true,
                            'resize' => true,
                            'height' => 300,
                        ]
                    ],
                    'use_tabs' => true,
                ]
            )
            ->add(
                'primaryUnitPrecision',
                ProductPrimaryUnitPrecisionType::class,
                [
                    'label'          => 'oro.product.primary_unit_precision.label',
                    'tooltip'        => 'oro.product.primary_unit_precision.tooltip',
                    'error_bubbling' => false,
                    'required'       => true,
                    'mapped'         => false,
                ]
            )
            ->add(
                'additionalUnitPrecisions',
                ProductUnitPrecisionCollectionType::class,
                [
                    'label'          => 'oro.product.additional_unit_precisions.label',
                    'tooltip'        => 'oro.product.additional_unit_precisions.tooltip',
                    'error_bubbling' => false,
                    'required'       => false,
                    'mapped'         => false,
                ]
            )
            ->add(
                'images',
                ProductImageCollectionType::class,
                ['required' => false]
            )
            ->add(
                'pageTemplate',
                EntityFieldFallbackValueType::class,
                [
                    'label' => 'oro.product.page_template.label',
                    'value_type' => PageTemplateType::class,
                    'value_options' => [
                        'route_name' => self::PAGE_TEMPLATE_ROUTE_NAME
                    ]
                ]
            )
            ->add('type', HiddenType::class, ['label' => 'oro.product.type.label'])

            ->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label'    => 'oro.product.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'names'
                ]
            )
            ->add('featured', ChoiceType::class, [
                'label' => 'oro.product.featured.label',
                'choices' => ['oro.product.featured.no' => 0, 'oro.product.featured.yes' => 1],
                'placeholder' => false,
            ])
            ->add('newArrival', ChoiceType::class, [
                'label' => 'oro.product.new_arrival.label',
                'tooltip' => 'oro.product.new_arrival.tooltip',
                'choices' => ['oro.product.new_arrival.no' => 0, 'oro.product.new_arrival.yes' => 1],
                'placeholder' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetDataListener'])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetDataListener'])
            ->addEventListener(FormEvents::SUBMIT, [$this, 'submitListener']);

        $builder->get('pageTemplate')
            ->addModelTransformer(new PageTemplateEntityFieldFallbackValueTransformer(self::PAGE_TEMPLATE_ROUTE_NAME));
    }

    public function preSetDataListener(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        if (!$product->getPageTemplate()) {
            $entityFallback = new EntityFieldFallbackValue();
            $entityFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
            $product->setPageTemplate($entityFallback);
        }
        $form->add(
            'variantFields',
            ProductCustomVariantFieldsCollectionType::class,
            [
                'label' => 'oro.product.variant_fields.label',
                'tooltip' => 'oro.product.variant_fields.tooltip',
                'required' => false,
                'attributeFamily' => $product->getAttributeFamily()
            ]
        );

        if ($product->getId() == null) {
            $form->remove('primaryUnitPrecision');
            $form->add(
                'primaryUnitPrecision',
                ProductPrimaryUnitPrecisionType::class,
                [
                    'label'          => 'oro.product.primary_unit_precision.label',
                    'tooltip'        => 'oro.product.primary_unit_precision.tooltip',
                    'error_bubbling' => false,
                    'required'       => true,
                    'data'           => $this->provider->getDefaultProductUnitPrecision()
                ]
            );
        }

        if ($product->getId()) {
            $url = $this->urlGenerator->generate('oro_product_get_changed_slugs', ['id' => $product->getId()]);

            $form->add(
                'slugPrototypesWithRedirect',
                LocalizedSlugWithRedirectType::class,
                [
                    'label'    => 'oro.product.slug_prototypes.label',
                    'required' => false,
                    'source_field' => 'names',
                    'get_changed_slugs_url' => $url
                ]
            );
        }

        if ($product->isConfigurable()) {
            $form
                ->add(
                    'variantLinks',
                    ProductVariantLinksType::class,
                    ['product_class' => $this->dataClass, 'by_reference' => false]
                );
        }

        if ($product->isKit()) {
            $form->add(
                'kitItems',
                ProductKitItemCollectionType::class,
                [
                    'label' => false,
                    'attr' => [
                        'class' => 'product-kit-control-group'
                    ],
                    'prototype_data' => (new ProductKitItem())->setProductKit($product),
                    'error_bubbling'=> false
                ]
            );
        }

        if (!$product->getImages()->isEmpty()) {
            $productImagesCollection = $product->getImages();
            $sortedProductImages = $this->productImageHelper->sortImages($productImagesCollection->toArray());
            $productImagesCollection->clear();

            foreach ($sortedProductImages as $image) {
                $productImagesCollection->add($image);
            }
        }
    }

    public function postSetDataListener(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        $primaryUnitPrecision = $product->getPrimaryUnitPrecision();

        // manual mapping
        $precisionForm = $form->get('primaryUnitPrecision');
        if (empty($precisionForm->getData()) && $primaryUnitPrecision instanceof ProductUnitPrecision) {
            // clone is required to prevent data modification by reference
            $precisionForm->setData(clone $product->getPrimaryUnitPrecision());
        }
        $form->get('additionalUnitPrecisions')->setData($product->getAdditionalUnitPrecisions());
    }

    public function submitListener(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        $primaryPrecision = $form->get('primaryUnitPrecision')->getData();
        if ($primaryPrecision) {
            $product->setPrimaryUnitPrecision($primaryPrecision);
        }

        /** @var ProductUnitPrecision[] $additionalPrecisions */
        $additionalPrecisions = $form->get('additionalUnitPrecisions')->getData();
        foreach ($additionalPrecisions as $key => $precision) {
            $existingPrecision = $product->getUnitPrecision($precision->getProductUnitCode());
            if ($existingPrecision) {
                // refresh precision object data to prevent problems with property accessor
                $product->addAdditionalUnitPrecision($precision);
                $additionalPrecisions[$key] = $existingPrecision;
            }
        }
        PropertyAccess::createPropertyAccessor()->setValue($product, 'additionalUnitPrecisions', $additionalPrecisions);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'csrf_token_id' => 'product',
            'enable_attributes' => true,
            'enable_attribute_family' => true,
        ]);
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
