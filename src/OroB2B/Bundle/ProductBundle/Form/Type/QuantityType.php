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

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;

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
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'roundQuantity'], -2048);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'roundQuantity'], -2048);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'roundQuantity'], -2048);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setDefaultData'], -1024);
    }

    /**
     * @param FormEvent $event
     */
    public function roundQuantity(FormEvent $event)
    {
        $scale = $this->getScale($event->getForm());
        if (!$scale) {
            return;
        }

        $quantity = $event->getData();
        $formattedQuantity = $this->roundingService->round($quantity, $scale);

        if ($quantity !== $formattedQuantity) {
            $event->setData($formattedQuantity);
        }
    }

    /**
     * @param FormInterface $form
     * @return int|null
     */
    protected function getScale(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $parent = $form->getParent();

        $product = $options['product'];
        $productField = $options['product_field'];

        if ($parent->has($productField)) {
            $productData = $parent->get($productField)->getData();
            if ($productData instanceof Product) {
                $product = $productData;
            }
        }

        if (!$product instanceof Product) {
            return null;
        }

        $productUnitField = $options['product_unit_field'];
        if (!$parent->has($productUnitField)) {
            throw new \InvalidArgumentException(sprintf('Missing "%s" on form', $productUnitField));
        }

        $productUnit = $parent->get($productUnitField)->getData();
        if (!$productUnit || !$productUnit instanceof ProductUnit) {
            if ($parent->get($productUnitField)->isRequired()) {
                throw new \InvalidArgumentException(sprintf('Missing "%s" data', $productUnitField));
            }

            return null;
        }

        $scale = $product->getUnitPrecision($productUnit->getCode());
        if ($scale) {
            return $scale->getPrecision();
        }

        return $productUnit->getDefaultPrecision();
    }

    /**
     * @param FormEvent $event
     */
    public function setDefaultData(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();

        $defaultData = $options['default_data'];
        if (!is_numeric($defaultData)) {
            return;
        }

        $data = $event->getData();
        if (!$data) {
            $event->setData($defaultData);
        }
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
                'constraints' => [new NotBlank(), new Range(['min' => 0]), new Decimal()],
                'precision_applied' => false,
            ]
        );

        $resolver->setAllowedTypes('product_unit_field', 'string');
        $resolver->setAllowedTypes('product_field', 'string');
    }

    /** {@inheritDoc} */
    public function getParent()
    {
        return 'text';
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return self::NAME;
    }
}
