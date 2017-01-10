<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\Type\AddressCollectionType;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Bundle\CustomerBundle\Event\CustomerEvent;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class CustomerType extends AbstractType
{
    const NAME = 'oro_customer_type';
    const GROUP_FIELD = 'group';

    /**
     * @var string
     */
    protected $addressClass;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $modelChangeSet = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['label' => 'oro.customer.customer.name.label'])
            ->add(
                self::GROUP_FIELD,
                CustomerGroupSelectType::NAME,
                [
                    'label' => 'oro.customer.customer.group.label',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                ParentCustomerSelectType::NAME,
                [
                    'label' => 'oro.customer.customer.parent.label',
                    'required' => false
                ]
            )
            ->add(
                'addresses',
                AddressCollectionType::NAME,
                [
                    'label' => 'oro.customer.customer.addresses.label',
                    'type' => CustomerTypedAddressType::NAME,
                    'required' => true,
                    'options' => [
                        'data_class' => $this->addressClass,
                        'single_form' => false
                    ]
                ]
            )
            ->add(
                'internal_rating',
                'oro_enum_select',
                [
                    'label' => 'oro.customer.customer.internal_rating.label',
                    'enum_code' => Customer::INTERNAL_RATING_CODE,
                    'configs' => [
                        'allowClear' => false,
                    ],
                    'required' => false
                ]
            )
            ->add(
                'salesRepresentatives',
                UserMultiSelectType::NAME,
                [
                    'label' => 'oro.customer.customer.sales_representatives.label',
                ]
            )
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $this->modelChangeSet = [];

        /** @var Customer $customer */
        $customer = $event->getForm()->getData();
        if ($customer instanceof Customer
            && $this->isCustomerGroupChanged($customer, (int)$event->getData()[self::GROUP_FIELD])
        ) {
            $this->modelChangeSet[] = self::GROUP_FIELD;
        }
    }

    /**
     * @param Customer $customer
     * @param int $newGroupId
     * @return bool
     */
    private function isCustomerGroupChanged(Customer $customer, $newGroupId)
    {
        return $customer->getGroup() && $newGroupId !== $customer->getGroup()->getId();
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Customer $customer */
        $customer = $event->getForm()->getData();
        if ($customer instanceof Customer
            && in_array(self::GROUP_FIELD, $this->modelChangeSet, true)
            && $event->getForm()->isValid()
        ) {
            $this->eventDispatcher->dispatch(
                CustomerEvent::ON_ACCOUNT_GROUP_CHANGE,
                new CustomerEvent($customer)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'cascade_validation' => true,
                'intention' => 'customer',
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

    /**
     * @param string $addressClass
     */
    public function setAddressClass($addressClass)
    {
        $this->addressClass = $addressClass;
    }
}
