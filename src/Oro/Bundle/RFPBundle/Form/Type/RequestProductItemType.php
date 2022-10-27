<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for RequestProductItem
 */
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
                PriceType::class,
                [
                    'currency_empty_value' => null,
                    'required' => true,
                    'label' => 'oro.rfp.requestproductitem.price.label',
                    'validation_groups' => ['Optional'],
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::class,
                [
                    'label' => 'oro.product.productunit.entity_label',
                    'required' => false,
                    'compact' => $options['compact_units'],
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => false,
                    'label' => 'oro.rfp.requestproductitem.quantity.label',
                    'default_data' => 1,
                    'useInputTypeNumberValueFormat' => true
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
                'csrf_token_id' => 'rfp_request_product_item',
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
