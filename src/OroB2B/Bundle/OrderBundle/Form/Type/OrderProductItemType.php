<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\OrderBundle\Entity\OrderProductItem;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductItemFormatter;

class OrderProductItemType extends AbstractType
{
    const NAME = 'orob2b_order_order_product_item';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var OrderProductItemFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param TranslatorInterface $translator
     * @param OrderProductItemFormatter $formatter
     */
    public function __construct(TranslatorInterface $translator, OrderProductItemFormatter $formatter)
    {
        $this->translator = $translator;
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
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
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

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /* @var $orderProductItem OrderProductItem */
        $orderProductItem = $event->getData();
        $form = $event->getForm();
        $choices = [];

        $productUnitOptions = [
            'required' => true,
            'label' => 'orob2b.product.productunit.entity_label',
        ];

        if ($orderProductItem && null !== $orderProductItem->getId()) {
            $product = $orderProductItem->getOrderProduct()->getProduct();
            if ($product) {
                foreach ($product->getUnitPrecisions() as $unitPrecision) {
                    $choices[] = $unitPrecision->getUnit();
                }
            }

            $productUnit = $orderProductItem->getProductUnit();
            if (!$productUnit || ($product && !in_array($productUnit, $choices, true))) {
                // productUnit was removed
                $productUnitOptions['empty_value'] = $this->translator->trans(
                    'orob2b.order.orderproduct.product.removed',
                    [
                        '{title}' => $orderProductItem->getProductUnitCode(),
                    ]
                );
            }
        }

        $productUnitOptions['choices'] = $choices;

        $form->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            $productUnitOptions
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $event->getForm()->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            [
                'label' => 'orob2b.product.productunit.entity_label',
            ]
        );
    }
}
