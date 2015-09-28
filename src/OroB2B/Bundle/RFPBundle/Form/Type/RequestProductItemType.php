<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType as PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;

class RequestProductItemType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_product_item';

    /**
     * @var string
     */
    protected $dataClass;

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
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = null;
        if (array_key_exists('data', $options)) {
            $requestProductItem = $options['data'];
        }

        $builder
            ->add(
                'price',
                PriceType::NAME,
                [
                    'currency_empty_value' => null,
                    'required' => true,
                    'label' => 'orob2b.rfp.requestproductitem.price.label',
                ]
            )
            ->add(
                'productUnit',
                ProductUnitRemovedSelectionType::NAME,
                [
                    'label' => 'orob2b.product.productunit.entity_label',
                    'required' => true,
                    'compact' => $options['compact_units'],
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.rfp.requestproductitem.quantity.label',
                    'product' => $requestProductItem ? $requestProductItem->getRequestProduct() : null,
                    'default_data' => 1,
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
                'compact_units' => false,
                'intention' => 'rfp_request_product_item',
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
