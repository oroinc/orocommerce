<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

class ProductType extends AbstractType
{
    const NAME = 'orob2b_product';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    private $provider;

    /**
     * @param DefaultProductUnitProviderInterface $provider
     */
    public function __construct(DefaultProductUnitProviderInterface $provider)
    {
        $this->provider = $provider;
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku', 'text', ['required' => true, 'label' => 'orob2b.product.sku.label'])
            ->add('status', ProductStatusType::NAME, ['label' => 'orob2b.product.status.label'])
            ->add(
                'inventoryStatus',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.product.inventory_status.label',
                    'enum_code' => 'prod_inventory_status',
                    'configs'   => ['allowClear' => false]
                ]
            )
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'orob2b.product.names.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank(['message' => 'orob2b.product.names.blank'])]],
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'orob2b.product.descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                            'width' => 500,
                            'height' => 300,
                            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
                            'toolbar' =>
                                [reset(OroRichTextType::$toolbars[OroRichTextType::TOOLBAR_DEFAULT]) . ' | fullscreen'],
                        ],
                    ],
                ]
            )
            ->add(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'orob2b.product.short_descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                            'width' => 500,
                            'height' => 300,
                            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullscreen']),
                            'toolbar' =>
                                [reset(OroRichTextType::$toolbars[OroRichTextType::TOOLBAR_DEFAULT]) . ' | fullscreen'],
                        ]
                    ]
                ]
            )
            ->add(
                'primaryUnitPrecision',
                ProductPrimaryUnitPrecisionType::NAME,
                [
                    'label'          => 'orob2b.product.primary_unit_precision.label',
                    'tooltip'        => 'orob2b.product.form.tooltip.unit_precision',
                    'error_bubbling' => false,
                    'required'       => true,
                ]
            )
            ->add(
                'additionalUnitPrecisions',
                ProductUnitPrecisionCollectionType::NAME,
                [
                    'label'          => 'orob2b.product.additional_unit_precisions.label',
                    'tooltip'        => 'orob2b.product.form.tooltip.unit_precision',
                    'error_bubbling' => false,
                    'required'       => false,
                ]
            )
            ->add(
                'variantFields',
                ProductCustomFieldsChoiceType::NAME,
                ['label' => 'orob2b.product.variant_fields.label']
            )->add(
                'images',
                ProductImageCollectionType::NAME
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetDataListener']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetDataListener(FormEvent $event)
    {
        $product = $event->getData();
        $form = $event->getForm();

        if ($product->getId() == null) {
            $form->remove('primaryUnitPrecision');
            $form->add(
                'primaryUnitPrecision',
                ProductPrimaryUnitPrecisionType::NAME,
                [
                    'label'          => 'orob2b.product.primary_unit_precision.label',
                    'tooltip'        => 'orob2b.product.form.tooltip.unit_precision',
                    'error_bubbling' => false,
                    'required'       => true,
                    'data'           => $this->provider->getDefaultProductUnitPrecision()
                ]
            );
        }
        if ($product instanceof Product && $product->getHasVariants()) {
            $form
                ->add(
                    'variantLinks',
                    ProductVariantLinksType::NAME,
                    ['product_class' => $this->dataClass, 'by_reference' => false]
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'           => $this->dataClass,
            'intention'            => 'product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
