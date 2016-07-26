<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
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
        $builder
            ->add(
                'price',
                PriceType::NAME,
                [
                    'currency_empty_value' => null,
                    'required' => true,
                    'label' => 'orob2b.rfp.requestproductitem.price.label',
                    'validation_groups' => ['Optional'],
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.product.productunit.entity_label',
                    'required' => false,
                    'compact' => $options['compact_units'],
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => false,
                    'label' => 'orob2b.rfp.requestproductitem.quantity.label',
                    'default_data' => 1,
                ]
            );

        // make value not empty
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var RequestProductItem $item */
                $item = $event->getData();
                if ($item) {
                    $item->updatePrice();
                }
            }
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
