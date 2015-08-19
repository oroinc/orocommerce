<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Formatter\OrderLineItemFormatter;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class OrderLineItemType extends AbstractType
{
    const NAME = 'orob2b_order_line_item';

    /**
     * @var OrderLineItemFormatter
     */
    protected $orderLineItemFormatter;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param OrderLineItemFormatter $orderLineItemFormatter
     * @param ProductUnitLabelFormatter $productUnitLabelFormatter
     */
    public function __construct(
        OrderLineItemFormatter $orderLineItemFormatter,
        ProductUnitLabelFormatter $productUnitLabelFormatter
    ) {
        $this->orderLineItemFormatter = $orderLineItemFormatter;
        $this->productUnitLabelFormatter = $productUnitLabelFormatter;
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
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $units = [];

        /* @var $products Product[] */
        $products = [];

        if ($view->vars['value']) {
            /* @var OrderLineItem $lineItem */
            $lineItem = $view->vars['value'];

            if ($lineItem->getProduct()) {
                $product = $lineItem->getProduct();
                $products[$product->getId()] = $product;
            }
        }

        foreach ($products as $product) {
            $units[$product->getId()] = [];

            foreach ($product->getAvailableUnitCodes() as $unitCode) {
                $units[$product->getId()][$unitCode] = $this->productUnitLabelFormatter->format($unitCode);
            }
        }

        $componentOptions = ['units' => $units];

        if (array_key_exists('componentOptions', $view->vars)) {
            $componentOptions = array_merge($view->vars['componentOptions'], $componentOptions);
        }

        $view->vars['componentOptions'] = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.product.entity_label',
                    'create_enabled' => false,
                ]
            )
            ->add(
                'productSku',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.sku.label'
                ]
            )
            ->add(
                'freeFormProduct',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.entity_label',
                ]
            )
            ->add(
                'quantity',
                'integer',
                [
                    'required' => true,
                    'label' => 'orob2b.order.orderlineitem.quantity.label',
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.product.productunit.entity_label',
                    'required' => true,
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'orob2b.order.orderlineitem.price.label',
                ]
            )
            ->add(
                'priceType',
                'choice',
                [
                    'label' => 'orob2b.order.orderlineitem.price_type.label',
                    'choices' => $this->orderLineItemFormatter->formatPriceTypeLabels(OrderLineItem::getPriceTypes()),
                    'required' => true,
                    'expanded' => true,
                ]
            )
            ->add(
                'shipBy',
                'oro_date',
                [
                    'required' => false,
                    'label' => 'orob2b.order.orderlineitem.ship_by.label',
                ]
            )
            ->add(
                'comment',
                'textarea',
                [
                    'required' => false,
                    'label' => 'orob2b.order.orderlineitem.comment.label',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'order_line_item',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
