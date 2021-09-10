<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductsGrouperFactory;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuickAddType extends AbstractType
{
    const NAME = 'oro_product_quick_add';

    const PRODUCTS_FIELD_NAME = 'products';
    const COMPONENT_FIELD_NAME = 'component';
    const ADDITIONAL_FIELD_NAME = 'additional';
    const TRANSITION_FIELD_NAME = 'transition';

    /** @var ProductsGrouperFactory */
    private $productsGrouperFactory;

    public function __construct(ProductsGrouperFactory $productsGrouperFactory)
    {
        $this->productsGrouperFactory = $productsGrouperFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRODUCTS_FIELD_NAME,
                ProductRowCollectionType::class,
                [
                    'required' => false,
                    'entry_options' => [
                        'validation_required' => $options['validation_required'],
                    ],
                    'error_bubbling' => true,
                    'constraints' => [new NotBlank(['message' => 'oro.product.at_least_one_item'])],
                    'add_label' => 'oro.product.form.add_row',
                    'products' => $options['products'],
                ]
            )
            ->add(
                self::COMPONENT_FIELD_NAME,
                HiddenType::class
            )
            ->add(
                self::ADDITIONAL_FIELD_NAME,
                HiddenType::class
            )
            ->add(
                self::TRANSITION_FIELD_NAME,
                HiddenType::class
            );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false,
                'products' => null,
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
        $resolver->setAllowedTypes('products', ['array', 'null']);
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
     * Remove duplicated products and combine their quantities
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!array_key_exists('products', $data)) {
            return;
        }

        // add an empty rows instead of removed duplicate products
        $numberOfRows = count($data['products']);
        $data['products'] = $this->productsGrouperFactory
            ->createProductsGrouper(ProductsGrouperFactory::ARRAY_PRODUCTS)
            ->process($data['products']);
        for ($i = count($data['products']); $i < $numberOfRows; $i++) {
            $data['products'][] = [
                ProductDataStorage::PRODUCT_DISPLAY_NAME => '',
                ProductDataStorage::PRODUCT_SKU_KEY => '',
                ProductDataStorage::PRODUCT_QUANTITY_KEY => '',
            ];
        }

        $event->setData($data);
    }
}
