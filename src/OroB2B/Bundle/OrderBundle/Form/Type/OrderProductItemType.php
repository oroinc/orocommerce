<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductItemFormatter;

class OrderProductItemType extends AbstractType
{
    const NAME = 'orob2b_order_order_product_item';

    /**
     * @var OrderProductItemFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param OrderProductItemFormatter $formatter
     */
    public function __construct(OrderProductItemFormatter $formatter)
    {
        $this->formatter = $formatter;
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
            ->add('quantity', 'integer', [
                'required' => true,
                'label' => 'orob2b.order.orderproductitem.quantity.label'
            ])
            ->add('price', PriceType::NAME, [
                'error_bubbling' => false,
                'required' => true,
                'label' => 'orob2b.order.orderproductitem.price.label'
            ])
            ->add('priceType', 'choice', [
                'label' => 'orob2b.order.orderproductitem.price_type.label',
                'choices' => $this->formatter->formatPriceTypeLabels(OrderProductItem::getPriceTypes()),
                'required' => true,
                'expanded' => true,
            ])
            ->add('productUnit', ProductUnitRemovedSelectionType::NAME, [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
            ]);
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'order_order_product_item',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
