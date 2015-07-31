<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Formatter\OrderProductFormatter;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class OrderProductType extends AbstractType
{
    const NAME = 'orob2b_order_order_product';

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $labelFormatter;

    /**
     * @var OrderProductFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param TranslatorInterface $translator
     * @param ProductUnitLabelFormatter $labelFormatter
     * @param OrderProductFormatter $formatter
     */
    public function __construct(
        TranslatorInterface $translator,
        ProductUnitLabelFormatter $labelFormatter,
        OrderProductFormatter $formatter
    ) {
        $this->translator = $translator;
        $this->labelFormatter = $labelFormatter;
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
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $units = [];

        /* @var $products Product[] */
        $products = [];

        if ($view->vars['value']) {
            /* @var $orderProduct OrderProduct */
            $orderProduct = $view->vars['value'];

            if ($orderProduct->getProduct()) {
                $product = $orderProduct->getProduct();
                $products[$product->getId()] = $product;
            }
        }

        foreach ($products as $product) {
            $units[$product->getId()] = [];

            foreach ($product->getAvailableUnitCodes() as $unitCode) {
                $units[$product->getId()][$unitCode] = $this->labelFormatter->format($unitCode);
            }
        }

        $componentOptions = [
            'units' => $units,
        ];

        $view->vars['componentOptions'] = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductSelectType::NAME, [
                'required' => true,
                'label' => 'orob2b.product.entity_label',
                'create_enabled' => false,
            ])
            ->add('orderProductItems', OrderProductItemCollectionType::NAME, [
                'add_label' => 'orob2b.order.orderproductitem.add_label',
            ])
            ->add('comment', 'textarea', [
                'required' => false,
                'label' => 'orob2b.order.orderproduct.comment.label',
            ])

        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'order_order_product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
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
        /* @var $orderProduct OrderProduct */
        $orderProduct = $event->getData();

        if (!$orderProduct || null === $orderProduct->getId()) {
            return;
        }

        $form = $event->getForm();

        if (!$orderProduct->getProduct()) {
            $this->replaceProductField(
                $form,
                'product',
                true,
                $orderProduct->getProductSku(),
                'orob2b.product.entity_label',
                'orob2b.order.orderproduct.product.removed'
            );
        }
    }

    /**
     * @param FormInterface $form
     * @param string $field
     * @param bool $required
     * @param string $productSku
     * @param string $label
     * @param string $emptyLabel
     */
    protected function replaceProductField(
        FormInterface $form,
        $field,
        $required,
        $productSku,
        $label,
        $emptyLabel = null
    ) {
        $options = [
            'create_enabled' => false,
            'required' => $required,
            'label' => $label,
        ];

        if ($emptyLabel) {
            $emptyValueTitle = $this->translator->trans($emptyLabel, [
                '{title}' => $productSku,
            ]);

            $options['configs'] = [
                'placeholder' => $emptyValueTitle,
            ];
        }

        $form->add($field, ProductSelectType::NAME, $options);
    }
}
