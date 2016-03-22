<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\OrderCurrencyHandler;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermSelectType;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class OrderType extends AbstractType
{
    const NAME = 'orob2b_order_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $websiteClass = 'OroB2B\Bundle\WebsiteBundle\Entity\Website';

    /**
     * @var OrderAddressSecurityProvider
     */
    protected $orderAddressSecurityProvider;

    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var OrderCurrencyHandler
     */
    protected $orderCurrencyHandler;

    /**
     * @param SecurityFacade $securityFacade
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param PaymentTermProvider $paymentTermProvider
     * @param OrderCurrencyHandler $orderCurrencyHandler
     */
    public function __construct(
        SecurityFacade $securityFacade,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        PaymentTermProvider $paymentTermProvider,
        OrderCurrencyHandler $orderCurrencyHandler
    ) {
        $this->securityFacade = $securityFacade;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->paymentTermProvider = $paymentTermProvider;
        $this->orderCurrencyHandler = $orderCurrencyHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $order */
        $order = $options['data'];
        $this->orderCurrencyHandler->setOrderCurrency($order);

        $builder
            ->add('account', AccountSelectType::NAME, ['label' => 'orob2b.order.account.label', 'required' => true])
            ->add(
                'accountUser',
                AccountUserSelectType::NAME,
                [
                    'label' => 'orob2b.order.account_user.label',
                    'required' => false,
                ]
            )
            ->add(
                'website',
                'entity',
                [
                    'class' => $this->websiteClass,
                    'label' => 'orob2b.order.website.label'
                ]
            )
            ->add('poNumber', 'text', ['required' => false, 'label' => 'orob2b.order.po_number.label'])
            ->add('shipUntil', OroDateType::NAME, ['required' => false, 'label' => 'orob2b.order.ship_until.label'])
            ->add('customerNotes', 'textarea', ['required' => false, 'label' => 'orob2b.order.customer_notes.label'])
            ->add('currency', 'hidden')
            ->add(
                'lineItems',
                OrderLineItemsCollectionType::NAME,
                [
                    'add_label' => 'orob2b.order.orderlineitem.add_label',
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
                    'label' => 'orob2b.order.shipping_cost.label',
                    'validation_groups' => ['Optional'],
                    'currencies_list' => [$order->getCurrency()]
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

        if ($this->isOverridePaymentTermGranted()) {
            $builder
                ->add(
                    'paymentTerm',
                    PaymentTermSelectType::NAME,
                    [
                        'label' => 'orob2b.order.payment_term.label',
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
     * @param FormBuilderInterface|FormInterface $form
     * @param Order $order
     */
    protected function addAddresses($form, Order $order)
    {
        if (!$form instanceof FormInterface && !$form instanceof FormBuilderInterface) {
            throw new \InvalidArgumentException('Invalid form');
        }

        $addressTypes = [
            AddressType::TYPE_BILLING => null,
            AddressType::TYPE_SHIPPING => OrderAddressType::APPLICATION_BACKEND,
        ];

        foreach ($addressTypes as $type => $application) {
            if ($this->orderAddressSecurityProvider->isAddressGranted($order, $type)) {
                $options = [
                    'label' => sprintf('orob2b.order.%s_address.label', $type),
                    'order' => $order,
                    'required' => false,
                    'addressType' => $type,
                ];

                if ($application) {
                    $options['application'] = $application;
                }

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
                'intention' => 'order',
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
        return self::NAME;
    }

    /**
     * @return bool
     */
    protected function isOverridePaymentTermGranted()
    {
        return $this->securityFacade->isGranted('orob2b_order_payment_term_account_can_override');
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
     * @param string $websiteClass
     */
    public function setWebsiteClass($websiteClass)
    {
        $this->websiteClass = $websiteClass;
    }
}
