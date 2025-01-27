<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\AddressValidationBundle\Form\Type\AddressValidatedAtType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents an order address in an order form.
 */
class OrderAddressType extends AbstractType
{
    public function __construct(
        private OrderAddressManager $orderAddressManager,
        private OrderAddressSecurityProvider $orderAddressSecurityProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'customerAddress',
                OrderAddressSelectType::class,
                [
                    'order' => $options['order'],
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
        $resolver->setDefaults([
            'data_class' => OrderAddress::class,
            'constraints' => [new NameOrOrganization()],
        ]);

        $resolver
            ->define('order')
            ->required()
            ->allowedTypes(CustomerOwnerAwareInterface::class);

        $resolver
            ->define('address_type')
            ->required()
            ->allowedValues(AddressTypeEntity::TYPE_BILLING, AddressTypeEntity::TYPE_SHIPPING);
    }

    #[\Override]
    public function getParent(): string
    {
        return AddressType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_address_type';
    }

    private function getOnPreSetDataClosure(): \Closure
    {
        return static function (FormEvent $event) {
            // Sets the previously selected address in the customer address drop-down.
            $orderAddress = $event->getData();
            if ($orderAddress === null) {
                $data = null;
            } elseif ($orderAddress->getCustomerAddress()) {
                $data = $orderAddress->getCustomerAddress();
            } elseif ($orderAddress->getCustomerUserAddress()) {
                $data = $orderAddress->getCustomerUserAddress();
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
        $isNewAddress = !$customerAddressData || $customerAddressData === OrderAddressSelectType::ENTER_MANUALLY;
        $addressType = $form->getConfig()->getOption('address_type');
        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($addressType);

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
            $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($addressType);
            if (!$isManualEditGranted) {
                $event->setData(null);
            }

            /** @var OrderAddress|null $orderAddress */
            $orderAddress = $event->getData();
            $selectedAddress = $form->get('customerAddress')->getData();
            if ($selectedAddress === null || $selectedAddress === OrderAddressSelectType::ENTER_MANUALLY) {
                if ($orderAddress && $isManualEditGranted) {
                    $orderAddress->setCustomerAddress(null);
                    $orderAddress->setCustomerUserAddress(null);
                }
            } elseif ($selectedAddress instanceof AbstractAddress
                && $this->isAddressChangeRequired($selectedAddress, $orderAddress)
            ) {
                $event->setData($this->orderAddressManager->updateFromAbstract($selectedAddress, $orderAddress));
            }
        };
    }

    private function isAddressChangeRequired(AbstractAddress $selectedAddress, ?OrderAddress $orderAddress): bool
    {
        if (!$orderAddress instanceof OrderAddress) {
            return true;
        }

        if (!$orderAddress->getCustomerAddress() && !$orderAddress->getCustomerUserAddress()) {
            return true;
        }

        if ($selectedAddress instanceof CustomerAddress &&
            $selectedAddress->getId() !== $orderAddress->getCustomerAddress()?->getId()) {
            return true;
        }

        if ($selectedAddress instanceof CustomerUserAddress &&
            $selectedAddress->getId() !== $orderAddress->getCustomerUserAddress()?->getId()) {
            return true;
        }

        return false;
    }
}
