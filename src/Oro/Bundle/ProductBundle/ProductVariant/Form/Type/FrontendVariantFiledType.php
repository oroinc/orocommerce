<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Form\Type;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
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
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Adds child form for each variant field
 */
class FrontendVariantFiledType extends AbstractType
{
    const NAME = 'oro_product_product_variant_frontend_variant_field';

    /** @var ProductVariantAvailabilityProvider */
    protected $productVariantAvailabilityProvider;

    /** @var ProductVariantTypeHandlerRegistry */
    protected $productVariantTypeHandlerRegistry;

    /** @var VariantFieldProvider */
    protected $variantFieldProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var string */
    protected $productClass;

    /** @var UserLocalizationManager */
    protected $userLocalizationManager;

    /**
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param ProductVariantTypeHandlerRegistry $productVariantTypeHandlerRegistry
     * @param VariantFieldProvider $variantFieldProvider
     * @param PropertyAccessor $propertyAccessor
     * @param string $productClass
     */
    public function __construct(
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        ProductVariantTypeHandlerRegistry $productVariantTypeHandlerRegistry,
        VariantFieldProvider $variantFieldProvider,
        PropertyAccessor $propertyAccessor,
        $productClass
    ) {
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->productVariantTypeHandlerRegistry = $productVariantTypeHandlerRegistry;
        $this->variantFieldProvider = $variantFieldProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->productClass = (string)$productClass;
    }

    /**
     * @param UserLocalizationManager $userLocalizationManager
     */
    public function setUserLocalizationManager(UserLocalizationManager $userLocalizationManager): void
    {
        $this->userLocalizationManager = $userLocalizationManager;
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

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $this->addVariantFields($event);
    }

    /**
     * @param FormEvent $event
     */
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
        $data = $this->getSimpleProductVariants($options['parentProduct']);

        if (!isset($view->vars['attr']['data-page-component-options']['simpleProductVariants'])) {
            $view->vars['attr']['data-page-component-options'] = json_encode(
                [
                    'simpleProductVariants' => $data['fields'] ?? [],
                    'simpleProductAttrs' => $data['attrs'] ?? [],
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
        $localization = $this->userLocalizationManager
            ? $this->userLocalizationManager->getCurrentLocalization()
            : null;

        $simpleProductVariants = [];

        foreach ($simpleProducts as $simpleProduct) {
            $simpleProductVariants['attrs'][$simpleProduct->getId()] = [
                'sku' => $simpleProduct->getSku(),
                'name' => (string) $simpleProduct->getName($localization),
            ];

            foreach ($variantFields as $key => $fieldName) {
                $value = $this->productVariantAvailabilityProvider
                    ->getVariantFieldScalarValue($simpleProduct, $fieldName);

                $simpleProductVariants['fields'][$simpleProduct->getId()][$fieldName] = $value;
            }
        }

        return $simpleProductVariants;
    }
}
