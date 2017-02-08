<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerRegistry;
use Oro\Bundle\ProductBundle\ProductVariant\Form\Type\DataTransformer\ProductVariantFieldsToProductVariantTransformer;

class FrontendVariantFiledType extends AbstractType
{
    const NAME = 'oro_product_product_variant_frontend_variant_field';

    /** @var ProductVariantAvailabilityProvider */
    protected $productVariantAvailabilityProvider;

    /** @var ProductVariantTypeHandlerRegistry */
    protected $productVariantTypeHandlerRegistry;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var string */
    protected $productClass;

    /**
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param ProductVariantTypeHandlerRegistry $productVariantTypeHandlerRegistry
     * @param PropertyAccessor $propertyAccessor
     * @param string $productClass
     */
    public function __construct(
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        ProductVariantTypeHandlerRegistry $productVariantTypeHandlerRegistry,
        PropertyAccessor $propertyAccessor,
        $productClass
    ) {
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->productVariantTypeHandlerRegistry = $productVariantTypeHandlerRegistry;
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

        foreach ($parentProduct->getVariantFields() as $fieldName) {
            $fieldType = $this->productVariantAvailabilityProvider->getCustomFieldType($fieldName);

            $variantTypeHandler = $this->productVariantTypeHandlerRegistry
                ->getVariantTypeHandler($fieldType);

            $subFormData = $this->propertyAccessor->getValue($data, $fieldName);

            $subForm = $variantTypeHandler
                ->createForm(
                    $fieldName,
                    $variantAvailability[$fieldName],
                    [
                        'data' => $subFormData,
                        'placeholder' => 'oro.product.type.please_select_option',
                        'empty_data'  => null
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
            'data_class' => $this->productClass
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
}
