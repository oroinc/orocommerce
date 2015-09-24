<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType as PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;

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
            ->add('quantity', 'integer', [
                'required'  => true,
                'label'     => 'orob2b.rfp.requestproductitem.quantity.label',
            ])
            ->add('price', PriceType::NAME, [
                'currency_empty_value' => null,
                'required'  => true,
                'label'     => 'orob2b.rfp.requestproductitem.price.label',
            ])
            ->add('productUnit', ProductUnitRemovedSelectionType::NAME, [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
                'compact' => $options['compact_units'],
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'compact_units' => false,
            'intention'  => 'rfp_request_product_item',
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
    public function postSetData(FormEvent $event)
    {
        // Set quantity to 1 by default
        $quantity = $event->getForm()->get('quantity');
        if (null === $quantity->getData()) {
            $quantity->setData(1);
        }
    }
}
