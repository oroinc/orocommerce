<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class RequestProductItemType extends AbstractType
{
    const NAME = 'oro_rfp_request_product_item';

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
                    'label' => 'oro.rfp.requestproductitem.price.label',
                    'hide_currency' => true,
                    'validation_groups' => ['Optional'],
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'oro.product.productunit.entity_label',
                    'required' => false,
                    'compact' => $options['compact_units'],
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => false,
                    'label' => 'oro.rfp.requestproductitem.quantity.label',
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
