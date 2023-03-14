<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Form\Type;

use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\DataTransformer\ProductVariantFieldsToProductVariantTransformer;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerRegistry;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Adds child form for each variant field
 */
class FrontendVariantFiledType extends AbstractType
{
    const NAME = 'oro_product_product_variant_frontend_variant_field';

    protected ProductVariantAvailabilityProvider $productVariantAvailabilityProvider;

    protected ProductVariantTypeHandlerRegistry $productVariantTypeHandlerRegistry;

    protected VariantFieldProvider $variantFieldProvider;

    protected LocalizationProviderInterface $localizationProvider;

    protected PropertyAccessorInterface $propertyAccessor;

    protected string $productClass;

    public function __construct(
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        ProductVariantTypeHandlerRegistry $productVariantTypeHandlerRegistry,
        VariantFieldProvider $variantFieldProvider,
        LocalizationProviderInterface $localizationProvider,
        PropertyAccessorInterface $propertyAccessor,
        $productClass
    ) {
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->productVariantTypeHandlerRegistry = $productVariantTypeHandlerRegistry;
        $this->variantFieldProvider = $variantFieldProvider;
        $this->localizationProvider = $localizationProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->productClass = (string)$productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new ProductVariantFieldsToProductVariantTransformer(
            $options['parentProduct'],
            $this->productVariantAvailabilityProvider,
            $this->productClass
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    public function preSetData(FormEvent $event)
    {
        $this->addVariantFields($event);
    }

    private function addVariantFields(FormEvent $event)
    {
        /** @var Product|null $data */
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $form = $event->getForm();

        /** @var Product $parentProduct */
        $parentProduct = $form->getConfig()->getOption('parentProduct');

        if (count($parentProduct->getVariantFields()) === 0) {
            return;
        }

        $variantAvailability = $this->productVariantAvailabilityProvider->getVariantFieldsAvailability($parentProduct);
        $variantFields = $this->variantFieldProvider->getVariantFields($parentProduct->getAttributeFamily());

        foreach ($parentProduct->getVariantFields() as $fieldName) {
            $fieldType = $this->productVariantAvailabilityProvider->getCustomFieldType($fieldName);

            $variantTypeHandler = $this->productVariantTypeHandlerRegistry
                ->getVariantTypeHandler($fieldType);

            $subFormData = $this->propertyAccessor->getValue($data, $fieldName);

            $subForm = $variantTypeHandler->createForm(
                $fieldName,
                $variantAvailability[$fieldName],
                [
                    'data' => $subFormData,
                    'label' => $variantFields[$fieldName]->getLabel(),
                    'placeholder' => 'oro.product.type.please_select_option',
                    'empty_data' => null
                ]
            );

            $form->add($subForm);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'parentProduct',
        ]);

        $resolver->setDefaults([
            'data_class' => $this->productClass,
            'attr' => [
                'data-page-component-module' => 'oroproduct/js/app/components/product-variant-field-component'
            ],
        ]);

        $resolver->setNormalizer('parentProduct', function (Options $options, Product $parentProduct) {
            if (!$parentProduct->isConfigurable()) {
                throw new \InvalidArgumentException('Parent product must have type "configurable"');
            }

            return $parentProduct;
        });

        $resolver->setAllowedTypes('parentProduct', $this->productClass);
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

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($view->vars['attr']['data-page-component-options']['simpleProductVariants'])) {
            $view->vars['attr']['data-page-component-options'] = json_encode(
                [
                    'simpleProductVariants' => $this->getSimpleProductVariants($options['parentProduct']),
                    'view' => 'oroproduct/js/app/views/base-product-variants-view'
                ]
            );
        }
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getSimpleProductVariants(Product $product)
    {
        $simpleProducts = $this->productVariantAvailabilityProvider->getSimpleProductsByVariantFields($product);
        $variantFields = $product->getVariantFields();
        $localization = $this->localizationProvider->getCurrentLocalization();

        $simpleProductVariants = [];

        foreach ($simpleProducts as $simpleProduct) {
            $data = [
                'sku' => $simpleProduct->getSku(),
                'name' => (string) $simpleProduct->getName($localization),
                'attributes' => [],
            ];

            foreach ($variantFields as $fieldName) {
                $data['attributes'][$fieldName] = $this->productVariantAvailabilityProvider
                    ->getVariantFieldScalarValue($simpleProduct, $fieldName);
            }

            $simpleProductVariants[$simpleProduct->getId()] = $data;
        }

        return $simpleProductVariants;
    }
}
