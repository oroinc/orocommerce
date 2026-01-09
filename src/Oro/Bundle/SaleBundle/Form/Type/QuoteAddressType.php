<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressValidationBundle\Form\Type\AddressValidatedAtType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for QuoteAddress entity.
 */
class QuoteAddressType extends AbstractType
{
    public function __construct(
        private QuoteAddressManager $quoteAddressManager,
        private QuoteAddressSecurityProvider $quoteAddressSecurityProvider,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'customerAddress',
                QuoteAddressSelectType::class,
                [
                    'quote' => $options['quote'],
                    'address_type' => $options['address_type'],
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->add(
                'phone',
                TextType::class,
                [
                    'required' => false,
                    StripTagsExtension::OPTION_NAME => true,
                ]
            )
            ->add('validatedAt', AddressValidatedAtType::class);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->getOnPreSetDataClosure());
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->getDisableFieldsOnPreSetDataClosure());
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->getDisableFieldsOnPreSubmitClosure());
        $builder->addEventListener(FormEvents::SUBMIT, $this->getOnSubmitClosure(), -10);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => QuoteAddress::class]);

        $resolver
            ->define('quote')
            ->required()
            ->allowedTypes(CustomerOwnerAwareInterface::class);

        $resolver
            ->define('address_type')
            ->required()
            ->allowedValues(AddressTypeEntity::TYPE_SHIPPING);
    }

    #[\Override]
    public function getParent(): string
    {
        return AddressType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_quote_address_type';
    }

    private function getOnPreSetDataClosure(): \Closure
    {
        return static function (FormEvent $event) {
            // Sets the previously selected address in the customer address drop-down.
            $quoteAddress = $event->getData();
            if ($quoteAddress === null) {
                $data = null;
            } elseif ($quoteAddress->getCustomerAddress()) {
                $data = $quoteAddress->getCustomerAddress();
            } elseif ($quoteAddress->getCustomerUserAddress()) {
                $data = $quoteAddress->getCustomerUserAddress();
            } else {
                $data = OrderAddressSelectType::ENTER_MANUALLY;
            }

            FormUtils::replaceFieldOptionsRecursive(
                $event->getForm(),
                'customerAddress',
                [
                    'data' => $data,
                ]
            );
        };
    }

    private function getDisableFieldsOnPreSetDataClosure(): \Closure
    {
        return function (FormEvent $event) {
            $customerAddressData = $event->getForm()->get('customerAddress')->getData();

            $this->doDisableFields($event, $customerAddressData);
        };
    }

    private function getDisableFieldsOnPreSubmitClosure(): \Closure
    {
        return function (FormEvent $event) {
            $customerAddressData = $event->getData()['customerAddress'] ?? null;

            $this->doDisableFields($event, (string) $customerAddressData);
        };
    }

    private function doDisableFields(FormEvent $event, AbstractAddress|string|int|null $customerAddressData): void
    {
        $form = $event->getForm();
        $isNewAddress = !$customerAddressData || $customerAddressData === QuoteAddressSelectType::ENTER_MANUALLY;
        $addressType = $form->getConfig()->getOption('address_type');
        $isManualEditGranted = $this->quoteAddressSecurityProvider->isManualEditGranted($addressType);

        foreach ($form as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'], true)) {
                continue;
            }

            if (!$isManualEditGranted || !$isNewAddress) {
                FormUtils::replaceFieldOptionsRecursive(
                    $event->getForm(),
                    $child->getName(),
                    ['disabled' => true]
                );
            }
        }
    }

    private function getOnSubmitClosure(): \Closure
    {
        return function (FormEvent $event) {
            $form = $event->getForm();
            $addressType = $form->getConfig()->getOption('address_type');
            $isManualEditGranted = $this->quoteAddressSecurityProvider->isManualEditGranted($addressType);
            if (!$isManualEditGranted) {
                $event->setData(null);
            }

            /** @var QuoteAddress|null $quoteAddress */
            $quoteAddress = $event->getData();
            $selectedAddress = $form->get('customerAddress')->getData();
            if ($selectedAddress === null || $selectedAddress === QuoteAddressSelectType::ENTER_MANUALLY) {
                if ($quoteAddress && $isManualEditGranted) {
                    $quoteAddress->setCustomerAddress(null);
                    $quoteAddress->setCustomerUserAddress(null);
                }
            } elseif (
                $selectedAddress instanceof AbstractAddress
                && $this->isAddressChangeRequired($selectedAddress, $quoteAddress)
            ) {
                $event->setData($this->quoteAddressManager->updateFromAbstract($selectedAddress, $quoteAddress));
            }
        };
    }

    private function isAddressChangeRequired(AbstractAddress $selectedAddress, ?QuoteAddress $quoteAddress): bool
    {
        if (!$quoteAddress instanceof QuoteAddress) {
            return true;
        }

        if (!$quoteAddress->getCustomerAddress() && !$quoteAddress->getCustomerUserAddress()) {
            return true;
        }

        if (
            $selectedAddress instanceof CustomerAddress &&
            $selectedAddress->getId() !== $quoteAddress->getCustomerAddress()?->getId()
        ) {
            return true;
        }

        if (
            $selectedAddress instanceof CustomerUserAddress &&
            $selectedAddress->getId() !== $quoteAddress->getCustomerUserAddress()?->getId()
        ) {
            return true;
        }

        return false;
    }
}
