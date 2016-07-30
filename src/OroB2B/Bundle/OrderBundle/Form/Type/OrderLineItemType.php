<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class OrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'orob2b_order_line_item';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $productUnitClass;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitFormatter;

    /**
     * @param ManagerRegistry $registry
     * @param ProductUnitLabelFormatter $productUnitFormatter
     */
    public function __construct(ManagerRegistry $registry, ProductUnitLabelFormatter $productUnitFormatter)
    {
        $this->registry = $registry;
        $this->productUnitFormatter = $productUnitFormatter;
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(
            'page_component_options',
            [
                'view' => 'orob2border/js/app/views/line-item-view',
                'freeFormUnits' => $this->getFreeFormUnits(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.product.entity_label',
                    'create_enabled' => false,
                    'data_parameters' => [
                        'scope' => 'order'
                    ]
                ]
            )
            ->add(
                'productSku',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.sku.label',
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
                'price',
                PriceType::NAME,
                [
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'orob2b.order.orderlineitem.price.label',
                    'hide_currency' => true,
                    'default_currency' => $options['currency'],
                ]
            )
            ->add('priceType', 'hidden', [
                'data' => OrderLineItem::PRICE_TYPE_UNIT,
            ]);
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
    protected function updateAvailableUnits(FormInterface $form)
    {
        /** @var OrderLineItem $item */
        $item = $form->getData();
        if (!$item->getProduct()) {
            return;
        }

        $form->remove('productUnit');
        $form->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
            ]
        );
    }

    /**
     * @return array
     */
    protected function getFreeFormUnits()
    {
        $units = $this->registry->getRepository($this->productUnitClass)->findBy([], ['code' => 'ASC']);
        $units = $this->productUnitFormatter->formatChoices($units);

        return $units;
    }
}
