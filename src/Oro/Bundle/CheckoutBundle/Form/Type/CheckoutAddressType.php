<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Layout\Provider\CheckoutThemeBCProvider;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents an address in a checkout form.
 */
class CheckoutAddressType extends AbstractType
{
    public function __construct(
        private OrderAddressSecurityProvider $orderAddressSecurityProvider,
        private CheckoutThemeBCProvider $checkoutThemeBCProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('customerAddress', CheckoutAddressSelectType::class, [
            'object' => $options['object'],
            'address_type' => $options['addressType'],
            'required' => true,
            'mapped' => false,
        ]);

        $builder->add('phone', TextType::class, [
            'required' => false,
            StripTagsExtension::OPTION_NAME => true,
        ]);

        $builder->add('country', CountryType::class, [
            'required' => true,
            'label' => 'oro.address.country.label',
        ]);

        $builder->add('region', RegionType::class, [
            'required' => true,
            'label' => 'oro.address.region.label',
        ]);

        $builder->add('validatedAt', CheckoutAddressValidatedAtType::class, [
            'checkout' => $options['object'],
            'address_type' => $options['addressType'],
        ]);
        $builder->get('city')->setRequired(true);
        $builder->get('postalCode')->setRequired(true);
        $builder->get('street')->setRequired(true);
        $builder->get('customerAddress')->setRequired(true);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $orderAddress = $event->getData();

            // Render previously saved address in address selector
            FormUtils::replaceFieldOptionsRecursive(
                $event->getForm(),
                'customerAddress',
                ['data' => $orderAddress]
            );

            // The check for ENTER_MANUALLY on customerAddress field is not made here because it does not work for
            // single page checkout. Instead, we check for customer / customer user address relation.
            if ($orderAddress && ($orderAddress->getCustomerAddress() || $orderAddress->getCustomerUserAddress())) {
                // Clears the address fields because if user chooses to enter address manually, these fields should be
                // shown empty.
                $event->setData(null);
            }
        }, 100);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $addressType = $form->getConfig()->getOption('addressType');
            $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($addressType);
            if (!$isManualEditGranted) {
                $event->setData(null);
            }

            if ($form->get('customerAddress')->getViewData() === CheckoutAddressSelectType::ENTER_MANUALLY) {
                return;
            }

            $selectedAddress = $form->get('customerAddress')->getData();
            if ($selectedAddress instanceof OrderAddress) {
                $event->setData($selectedAddress);
            }
        }, -10);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit(...));
    }

    public function onPreSetData(FormEvent $event): void
    {
        $orderAddress = $event->getData();

        // Render previously saved address in address selector
        FormUtils::replaceFieldOptionsRecursive(
            $event->getForm(),
            'customerAddress',
            ['data' => $orderAddress]
        );

        // The check for ENTER_MANUALLY on customerAddress field is not made here because it does not work for
        // single page checkout. Instead, we check for customer / customer user address relation.
        if ($orderAddress && ($orderAddress->getCustomerAddress() || $orderAddress->getCustomerUserAddress())) {
            // Clears the address fields because if user chooses to enter address manually, these fields should be
            // shown empty.
            $event->setData(null);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(['disableManualFields', 'disabled'])
            ->setRequired(['object', 'addressType'])
            ->setDefaults([
                'data_class' => OrderAddress::class,
                'constraints' => [new NameOrOrganization()],
                'disableManualFields' => false,
            ])
            ->setAllowedValues('addressType', [AddressTypeEntity::TYPE_BILLING, AddressTypeEntity::TYPE_SHIPPING])
            ->setAllowedTypes('disableManualFields', 'bool')
            ->setAllowedTypes('object', Checkout::class);

        $resolver
            ->addNormalizer('disabled', function ($options, $value) {
                return $value ?? false;
            });
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_address';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return AddressType::class;
    }

    public function onPreSubmit(FormEvent $event): void
    {
        if ($this->checkoutThemeBCProvider->isOldTheme()) {
            return;
        }

        $form = $event->getForm();
        $customerAddressData = $event->getData()['customerAddress'] ?? null;
        $isNewAddress = !$customerAddressData ||
            $customerAddressData === CheckoutAddressSelectType::ENTER_MANUALLY;

        foreach ($form as $child) {
            if ($child->getName() === 'customerAddress') {
                continue;
            }

            if (!$isNewAddress || (
                $customerAddressData === CheckoutAddressSelectType::ENTER_MANUALLY &&
                    $form->getConfig()->getOption('disableManualFields')
            )) {
                FormUtils::replaceFieldOptionsRecursive(
                    $event->getForm(),
                    $child->getName(),
                    [
                        'disabled' => true,
                    ]
                );
            }
        }
    }
}
