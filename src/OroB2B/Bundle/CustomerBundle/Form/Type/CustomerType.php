<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use OroB2B\src\OroB2B\Bundle\CustomerBundle\EventListener\CustomerTypeEventSubscriber;

class CustomerType extends AbstractType
{
    const NAME = 'orob2b_customer_type';

    /** @var  Translator */
    protected $translator;

    /** @var string */
    protected $addressClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['label' => 'orob2b.customer.name.label'])
            ->add(
                'group',
                CustomerGroupSelectType::NAME,
                [
                    'label' => 'orob2b.customer.group.label',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                ParentCustomerSelectType::NAME,
                [
                    'label' => 'orob2b.customer.parent.label',
                    'required' => false
                ]
            )
            ->add(
                'addresses',
                'oro_address_collection',
                [
                    'label'    => 'orob2b.customer.addresses.label',
                    'type'     => CustomerTypedAddressType::NAME,
                    'required' => true,
                    'options'  => [
                        'data_class'  => $this->addressClass,
                        'single_form' => false
                    ]
                ]
            )
            ->add(
                'internal_rating',
                'oro_enum_select',
                [
                    'label'     => 'orob2b.customer.internal_rating.label',
                    'enum_code' => Customer::INTERNAL_RATING_CODE,
                    'configs' => [
                        'allowClear' => false,
                    ],
                    'required' => false
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $addressClass
     */
    public function setAddressClass($addressClass)
    {
        $this->addressClass = $addressClass;
    }
}
