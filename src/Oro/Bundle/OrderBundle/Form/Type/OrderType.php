<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Form\Type\AccountSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber;
use Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

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
     * @throws \InvalidArgumentException
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
            ->add('poNumber', TextType::class, ['required' => false, 'label' => 'oro.order.po_number.label'])
            ->add('shipUntil', OroDateType::NAME, ['required' => false, 'label' => 'oro.order.ship_until.label'])
            ->add(
                'customerNotes',
                TextareaType::class,
                ['required' => false, 'label' => 'oro.order.customer_notes.label']
            )
            ->add('currency', HiddenType::class)
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
                'overriddenShippingCost',
                NumberType::class,
                [
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [new Decimal()],
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
                HiddenType::class,
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

            ->add('sourceEntityClass', HiddenType::class)
            ->add('sourceEntityId', HiddenType::class)
            ->add('sourceEntityIdentifier', HiddenType::class)
            ->add('shippingMethod', HiddenType::class)
            ->add('shippingMethodType', HiddenType::class)
            ->add('estimatedShippingCost', HiddenType::class, ['mapped' => false])
            ->add(
                OrderPossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY,
                HiddenType::class,
                [
                    'mapped' => false
                ]
            );

        $this->addAddresses($builder, $order);
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {

                /** @var Order $order */
                $order = $event->getData();
                $form = $event->getForm();

                if ($order->getEstimatedShippingCost()) {
                    $form->get('estimatedShippingCost')->setData($order->getEstimatedShippingCost()->getValue());
                }
                if ($order->getOverriddenShippingCost()) {
                    $form->get('overriddenShippingCost')->setData($order->getOverriddenShippingCost()->getValue());
                }
            }
        );
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $this->addAddresses($event->getForm(), $event->getData());

                /** @var Order $order */
                $order = $event->getData();
                $form = $event->getForm();

                $currency = $form->get('currency')->getData();
                $estimatedShippingCostAmount = $form->get('estimatedShippingCost')->getData();
                $overriddenShippingCostAmount = $form->get('overriddenShippingCost')->getData();

                $order->setEstimatedShippingCost(Price::create($estimatedShippingCostAmount, $currency));
                $order->setOverriddenShippingCost(Price::create($overriddenShippingCostAmount, $currency));

                $event->setData($order);
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
     * @throws \InvalidArgumentException
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
     * @throws AccessException
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
