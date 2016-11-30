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
use Oro\Bundle\CustomerBundle\Event\AccountEvent;
use Oro\Bundle\CustomerBundle\Entity\Account;

class AccountType extends AbstractType
{
    const NAME = 'oro_account_type';
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
            ->add('name', 'text', ['label' => 'oro.customer.account.name.label'])
            ->add(
                self::GROUP_FIELD,
                AccountGroupSelectType::NAME,
                [
                    'label' => 'oro.customer.account.group.label',
                    'required' => false
                ]
            )
            ->add(
                'parent',
                ParentAccountSelectType::NAME,
                [
                    'label' => 'oro.customer.account.parent.label',
                    'required' => false
                ]
            )
            ->add(
                'addresses',
                AddressCollectionType::NAME,
                [
                    'label' => 'oro.customer.account.addresses.label',
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
                    'label' => 'oro.customer.account.internal_rating.label',
                    'enum_code' => Account::INTERNAL_RATING_CODE,
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
                    'label' => 'oro.customer.account.sales_representatives.label',
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

        /** @var Account $account */
        $account = $event->getForm()->getData();
        if ($account instanceof Account
            && $this->isAccountGroupChanged($account, (int)$event->getData()[self::GROUP_FIELD])
        ) {
            $this->modelChangeSet[] = self::GROUP_FIELD;
        }
    }

    /**
     * @param Account $account
     * @param int $newGroupId
     * @return bool
     */
    private function isAccountGroupChanged(Account $account, $newGroupId)
    {
        return $account->getGroup() && $newGroupId !== $account->getGroup()->getId();
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Account $account */
        $account = $event->getForm()->getData();
        if ($account instanceof Account
            && in_array(self::GROUP_FIELD, $this->modelChangeSet, true)
            && $event->getForm()->isValid()
        ) {
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
