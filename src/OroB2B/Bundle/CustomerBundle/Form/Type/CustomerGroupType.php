<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerGroupType extends AbstractType
{
    const NAME = 'orob2b_customer_group_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $customerClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $customerClass
     */
    public function setCustomerClass($customerClass)
    {
        $this->customerClass = $customerClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                'text',
                [
                    'label' => 'orob2b.customer.customergroup.name.label',
                    'required' => true
                ]
            )
            ->add(
                'paymentTerm',
                PaymentTermSelectType::NAME,
                [
                    'label' => 'orob2b.customer.customergroup.paymentterm.label',
                ]
            )
            ->add(
                'appendCustomers',
                'oro_entity_identifier',
                [
                    'class'    => $this->customerClass,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            )
            ->add(
                'removeCustomers',
                'oro_entity_identifier',
                [
                    'class'    => $this->customerClass,
                    'required' => false,
                    'mapped'   => false,
                    'multiple' => true
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => $this->dataClass]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
