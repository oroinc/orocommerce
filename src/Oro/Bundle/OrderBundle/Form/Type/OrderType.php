<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserSelectType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;

class OrderType extends AbstractType
{
    const NAME = 'oro_order_type';

    /** @var string */
    protected $dataClass;

    /** @var OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var OrderCurrencyHandler */
    protected $orderCurrencyHandler;

    /** @var SubtotalSubscriber */
    protected $subtotalSubscriber;

    /**
     * @param SecurityFacade $securityFacade
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param PaymentTermProvider $paymentTermProvider
     * @param OrderCurrencyHandler $orderCurrencyHandler
     * @param SubtotalSubscriber $subtotalSubscriber
     */
    public function __construct(
        SecurityFacade $securityFacade,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        PaymentTermProvider $paymentTermProvider,
        OrderCurrencyHandler $orderCurrencyHandler,
        SubtotalSubscriber $subtotalSubscriber
    ) {
        $this->securityFacade = $securityFacade;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->paymentTermProvider = $paymentTermProvider;
        $this->orderCurrencyHandler = $orderCurrencyHandler;
        $this->subtotalSubscriber = $subtotalSubscriber;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $order */
        $order = $options['data'];
        $this->orderCurrencyHandler->setOrderCurrency($order);

        $builder
            ->add('account', AccountSelectType::NAME, ['label' => 'oro.order.account.label', 'required' => true])
            ->add(
                'accountUser',
                AccountUserSelectType::NAME,
                [
                    'label' => 'oro.order.account_user.label',
                    'required' => false,
                ]
            )
            ->add('poNumber', 'text', ['required' => false, 'label' => 'oro.order.po_number.label'])
            ->add('shipUntil', OroDateType::NAME, ['required' => false, 'label' => 'oro.order.ship_until.label'])
            ->add('customerNotes', 'textarea', ['required' => false, 'label' => 'oro.order.customer_notes.label'])
            ->add('currency', 'hidden')
            ->add(
                'lineItems',
                OrderLineItemsCollectionType::NAME,
                [
                    'add_label' => 'oro.order.orderlineitem.add_label',
                    'cascade_validation' => true,
                    'options' => ['currency' => $order->getCurrency()]
                ]
            )
            ->add(
                'shippingCost',
                PriceType::NAME,
                [
                    'currency_empty_value' => null,
                    'error_bubbling' => false,
                    'required' => false,
                    'label' => 'oro.order.shipping_cost.label',
                    'validation_groups' => ['Optional'],
                    'currencies_list' => [$order->getCurrency()]
                ]
            )
            ->add(
                'discounts',
                OrderDiscountItemsCollectionType::NAME,
                [
                    'add_label' => 'oro.order.discountitem.add_label',
                    'cascade_validation' => true,
                    'options' => [
                        'currency' => $order->getCurrency(),
                        'total' => pow(10, 18) - 1,
                    ]
                ]
            )
            ->add(
                'discountsSum',
                'hidden',
                [
                    'mapped' => false,
                    //range should be used, because this type also is implemented with JS
                    'constraints' => [new Range(
                        [
                            'min' => PHP_INT_MAX * (-1), //use some big negative number
                            'max' => $order->getSubtotal(),
                            'maxMessage' => 'oro.order.discounts.sum.error.label'
                        ]
                    )],
                    'data' => $order->getTotalDiscounts() ? $order->getTotalDiscounts()->getValue() : 0
                ]
            )

            ->add('sourceEntityClass', 'hidden')
            ->add('sourceEntityId', 'hidden')
            ->add('sourceEntityIdentifier', 'hidden');

        $this->addAddresses($builder, $order);
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $this->addAddresses($event->getForm(), $event->getData());
            }
        );

        $this->addBillingAddress($builder, $order, $options);
        $this->addShippingAddress($builder, $order, $options);
        $this->addPaymentTerm($builder, $order);

        $builder->addEventSubscriber($this->subtotalSubscriber);
    }

    /**
     * @param FormBuilderInterface|FormInterface $form
     * @param Order $order
     */
    protected function addAddresses($form, Order $order)
    {
        if (!$form instanceof FormInterface && !$form instanceof FormBuilderInterface) {
            throw new \InvalidArgumentException('Invalid form');
        }

        foreach ([AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING] as $type) {
            if ($this->orderAddressSecurityProvider->isAddressGranted($order, $type)) {
                $options = [
                    'label' => sprintf('oro.order.%s_address.label', $type),
                    'object' => $order,
                    'required' => false,
                    'addressType' => $type,
                ];

                $form->add(sprintf('%sAddress', $type), OrderAddressType::NAME, $options);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'order'
            ]
        );
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @return string
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
     * @return bool
     */
    protected function isOverridePaymentTermGranted()
    {
        return $this->securityFacade->isGranted('oro_order_payment_term_account_can_override');
    }

    /**
     * @param Order $order
     * @return int|null
     */
    protected function getAccountPaymentTermId(Order $order)
    {
        $account = $order->getAccount();
        if (!$account) {
            return null;
        }

        $paymentTerm = $this->paymentTermProvider->getAccountPaymentTerm($account);

        return $paymentTerm ? $paymentTerm->getId() : null;
    }

    /**
     * @param Order $order
     * @return int|null
     */
    protected function getAccountGroupPaymentTermId(Order $order)
    {
        $account = $order->getAccount();
        if (!$account || !$account->getGroup()) {
            return null;
        }

        $paymentTerm = $this->paymentTermProvider->getAccountGroupPaymentTerm($account->getGroup());

        return $paymentTerm ? $paymentTerm->getId() : null;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Order $order
     */
    protected function addPaymentTerm(FormBuilderInterface $builder, Order $order)
    {
        if ($this->isOverridePaymentTermGranted()) {
            $builder
                ->add(
                    'paymentTerm',
                    PaymentTermSelectType::NAME,
                    [
                        'label' => 'oro.order.payment_term.label',
                        'required' => false,
                        'attr' => [
                            'data-account-payment-term' => $this->getAccountPaymentTermId($order),
                            'data-account-group-payment-term' => $this->getAccountGroupPaymentTermId($order),
                        ],
                    ]
                );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Order $order
     * @param array $options
     */
    protected function addBillingAddress(FormBuilderInterface $builder, Order $order, $options)
    {
        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_BILLING)) {
            $builder
                ->add(
                    'billingAddress',
                    OrderAddressType::NAME,
                    [
                        'label' => 'oro.order.billing_address.label',
                        'object' => $options['data'],
                        'required' => false,
                        'addressType' => AddressType::TYPE_BILLING,
                        'isEditEnabled' => true
                    ]
                );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param Order $order
     * @param array $options
     */
    protected function addShippingAddress(FormBuilderInterface $builder, Order $order, $options)
    {
        if ($this->orderAddressSecurityProvider->isAddressGranted($order, AddressType::TYPE_SHIPPING)) {
            $builder
                ->add(
                    'shippingAddress',
                    OrderAddressType::NAME,
                    [
                        'label' => 'oro.order.shipping_address.label',
                        'object' => $options['data'],
                        'required' => false,
                        'addressType' => AddressType::TYPE_SHIPPING,
                        'isEditEnabled' => true
                    ]
                );
        }
    }
}
