<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class QuantityType extends AbstractType
{
    const NAME = 'orob2b_quantity';

    /** @var RoundingService */
    protected $roundingService;

    /** @var string */
    protected $productClass;

    /**
     * @param RoundingService $roundingService
     * @param $productClass
     */
    public function __construct(RoundingService $roundingService, $productClass)
    {
        $this->roundingService = $roundingService;
        $this->productClass = $productClass;
    }

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($options) {
                if ($options['precision']) {
                    return;
                }

                $form = $event->getForm()->getParent();

                $product = $options['product'];

                $productField = $options['product_field'];
                if ($form->has($productField)) {
                    $productData = $form->get($productField)->getData();
                    if ($productData instanceof Product) {
                        $product = $productData;
                    }
                }

                if (!$product instanceof Product) {
                    return;
                }

                $productUnitField = $options['product_unit_field'];
                if (!$form->has($productUnitField)) {
                    throw new \InvalidArgumentException(sprintf('Missing "%s" on form', $productUnitField));
                }

                $productUnit = $form->get($productUnitField)->getData();
                if (!$productUnit || !$productUnit instanceof ProductUnit) {
                    return;
                }

                $precision = $product->getUnitPrecision($productUnit->getCode());
                if ($precision) {
                    $precision = $precision->getPrecision();
                } else {
                    $precision = $productUnit->getDefaultPrecision();
                }

                $quantity = $event->getData();
                $formattedQuantity = $this->roundingService->round($quantity, $precision);
                $event->setData($formattedQuantity);

                $this->replaceQuantity($form, $precision);
            },
            -255
        );

        // Set quantity by default
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($options) {
                $defaultData = $options['default_data'];
                if (!is_numeric($defaultData)) {
                    return;
                }

                $data = $event->getData();
                if (!$data) {
                    $event->setData($defaultData);
                }
            }
        );
    }

    /**
     * @param FormInterface $form
     * @param mixed $precision
     */
    protected function replaceQuantity(FormInterface $form, $precision)
    {
        $options = $form->get('quantity')->getConfig()->getOptions();

        $form->add(
            'quantity',
            self::NAME,
            array_merge($options, ['precision' => $precision, 'precision_applied' => true])
        );
    }

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'product' => null,
                'product_unit_field' => 'productUnit',
                'product_field' => 'product',
                'default_data' => null,
                'constraints' => [new NotBlank(), new Range(['min' => 0])],
                'precision_applied' => false,
            ]
        );

//        $resolver->setAllowedTypes('product', $this->productClass);
        $resolver->setAllowedTypes('product_unit_field', 'string');
        $resolver->setAllowedTypes('product_field', 'string');
    }

    /** {@inheritDoc} */
    public function getParent()
    {
        return 'number';
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return self::NAME;
    }
}
