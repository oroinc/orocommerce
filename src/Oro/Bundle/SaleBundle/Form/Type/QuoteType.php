<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrderBundle\EventListener\PossibleShippingMethodEventListener;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Bundle\UserBundle\Form\Type\UserSelectType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * The form type for Quote entity.
 */
class QuoteType extends AbstractType
{
    public const NAME = 'oro_sale_quote';

    private QuoteAddressSecurityProvider $quoteAddressSecurityProvider;
    private ConfigManager $configManager;
    private EventSubscriberInterface $quoteFormSubscriber;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        QuoteAddressSecurityProvider $quoteAddressSecurityProvider,
        ConfigManager $configManager,
        EventSubscriberInterface $quoteFormSubscriber,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->quoteAddressSecurityProvider = $quoteAddressSecurityProvider;
        $this->configManager = $configManager;
        $this->quoteFormSubscriber = $quoteFormSubscriber;
        $this->authorizationChecker = $authorizationChecker;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Quote $quote */
        $quote = $options['data'];
        $defaultCurrency = $this->configManager->get(CurrencyConfig::getConfigKeyByName(
            CurrencyConfig::KEY_DEFAULT_CURRENCY
        ));
        $quote->setCurrency($defaultCurrency);

        $builder
            ->add('qid', HiddenType::class)
            ->add('owner', UserSelectType::class, [
                'label' => 'oro.sale.quote.owner.label',
                'required' => true
            ])
            ->add('customerUser', CustomerUserSelectType::class, [
                'label' => 'oro.sale.quote.customer_user.label',
                'required' => false
            ])
            ->add('customer', CustomerSelectType::class, [
                'label' => 'oro.sale.quote.customer.label',
                'required' => false
            ])
            ->add('validUntil', OroDateTimeType::class, [
                'label' => 'oro.sale.quote.valid_until.label',
                'required' => false
            ])
            ->add('shippingMethodLocked', CheckboxType::class, [
                'label' => 'oro.sale.quote.shipping_method_locked.label',
                'required' => false
            ])
            ->add('allowUnlistedShippingMethod', CheckboxType::class, [
                'label' => 'oro.sale.quote.allow_unlisted_shipping_method.label',
                'required' => false
            ])
            ->add('poNumber', TextType::class, [
                'required' => false,
                'label' => 'oro.sale.quote.po_number.label'
            ])
            ->add('shipUntil', OroDateType::class, [
                'required' => false,
                'label' => 'oro.sale.quote.ship_until.label'
            ])
            ->add(
                'quoteProducts',
                QuoteProductCollectionType::class,
                [
                    'add_label' => 'oro.sale.quoteproduct.add_label',
                    'entry_options' => [
                        'compact_units' => true,
                        'allow_prices_override' => $options['allow_prices_override'],
                        'allow_add_free_form_items' => $options['allow_add_free_form_items'],
                    ]
                ]
            )
            ->add('assignedUsers', UserMultiSelectType::class, [
                'label' => 'oro.sale.quote.assigned_users.label',
            ])
            ->add('assignedCustomerUsers', CustomerUserMultiSelectType::class, [
                'label' => 'oro.sale.quote.assigned_customer_users.label',
            ]);
        $this->addShippingFields($builder, $quote);

        $builder->addEventSubscriber($this->quoteFormSubscriber);

        if ($this->quoteAddressSecurityProvider->isAddressGranted($quote, AddressType::TYPE_SHIPPING)) {
            $builder
                ->add(
                    'shippingAddress',
                    QuoteAddressType::class,
                    [
                        'label' => 'oro.sale.quote.shipping_address.label',
                        'quote' => $options['data'],
                        'required' => false,
                        'address_type' => AddressType::TYPE_SHIPPING,
                    ]
                );
        }
    }

    private function addShippingFields(FormBuilderInterface $builder, Quote $quote): void
    {
        $builder
            ->add(PossibleShippingMethodEventListener::CALCULATE_SHIPPING_KEY, HiddenType::class, [
                'mapped' => false
            ])
            ->add('shippingMethod', HiddenType::class)
            ->add('shippingMethodType', HiddenType::class)
            ->add('estimatedShippingCostAmount', HiddenType::class)
            ->add('overriddenShippingCostAmount', PriceType::class, [
                'label' => 'oro.sale.quote.overridden_shipping_cost_amount.label',
                'required' => false,
                'validation_groups' => ['Optional'],
                'hide_currency' => true,
            ])
            ->get('overriddenShippingCostAmount')->addModelTransformer(new CallbackTransformer(
                function ($amount) use ($quote) {
                    return $amount ? Price::create($amount, $quote->getCurrency()) : null;
                },
                function ($price) {
                    return $price instanceof Price ? $price->getValue() : $price;
                }
            ));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
            'csrf_token_id' => 'sale_quote',
            'allow_prices_override' => $this->authorizationChecker->isGranted('oro_quote_prices_override'),
            'allow_add_free_form_items' => $this->authorizationChecker->isGranted('oro_quote_add_free_form_items'),
            'validation_groups' => new GroupSequence([
                Constraint::DEFAULT_GROUP,
                'add_kit_item_line_item',
                'quote_is_valid_for_sending_to_customer'
            ]),
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
