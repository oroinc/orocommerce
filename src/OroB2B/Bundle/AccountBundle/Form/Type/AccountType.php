<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\Type\AddressCollectionType;

use OroB2B\Bundle\AccountBundle\Event\AccountEvent;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountType extends AbstractType
{
    const NAME = 'orob2b_account_type';

    /** @var string */
    protected $addressClass;

    /** @var   EventDispatcherInterface */
    protected $eventDispatcher;

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
            ->add('name', 'text', ['label' => 'orob2b.account.name.label'])
            ->add(
                'group',
                AccountGroupSelectType::NAME,
                [
                    'label' => 'orob2b.account.group.label',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                ParentAccountSelectType::NAME,
                [
                    'label' => 'orob2b.account.parent.label',
                    'required' => false
                ]
            )
            ->add(
                'addresses',
                AddressCollectionType::NAME,
                [
                    'label' => 'orob2b.account.addresses.label',
                    'type' => AccountTypedAddressType::NAME,
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
                    'label' => 'orob2b.account.internal_rating.label',
                    'enum_code' => Account::INTERNAL_RATING_CODE,
                    'configs' => [
                        'allowClear' => false,
                    ],
                    'required' => false
                ]
            )
            ->add(
                'salesRepresentatives',
                SalesRepresentativesCollectionType::NAME,
                [
                    'label' => 'orob2b.account.sales_representatives.label',
                ]
            )
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $newGroupId = (int)$event->getData()['group'];
        /** @var Account $account */
        $account = $event->getForm()->getData();
        if ($account instanceof Account && $account->getGroup() && $newGroupId !== $account->getGroup()->getId()) {
            $this->eventDispatcher->dispatch(
                AccountEvent::ON_ACCOUNT_GROUP_CHANGE,
                new AccountEvent($account)
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
                'intention' => 'account',
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

    /**
     * @param string $addressClass
     */
    public function setAddressClass($addressClass)
    {
        $this->addressClass = $addressClass;
    }
}
