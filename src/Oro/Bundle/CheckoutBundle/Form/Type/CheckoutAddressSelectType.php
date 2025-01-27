<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\DataTransformer\OrderAddressToAddressIdentifierViewTransformer;
use Oro\Bundle\CustomerBundle\Entity\AddressBookAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Utils\AddressBookAddressUtils;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents checkout address select form type with list of grouped available addresses for customer user
 */
class CheckoutAddressSelectType extends AbstractType
{
    public const string ENTER_MANUALLY = '0';

    public function __construct(
        private OrderAddressManager $orderAddressManager,
        private AddressFormatter $addressFormatter,
        private OrderAddressSecurityProvider $orderAddressSecurityProvider,
        private Serializer $serializer,
        private OrderAddressToAddressIdentifierViewTransformer $orderAddressToAddressIdentifierViewTransformer
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Checkout $object */
        $object = $options['object'];
        $addressType = $options['address_type'];
        /** @var TypedOrderAddressCollection $collection */
        $collection = $options['address_collection'];

        $address = $this->getSelectedAddress($object, $addressType);
        if ($address === null) {
            $address = $collection->getDefaultAddressKey();
        }

        if ($address !== null) {
            $builder->setData($address);
        }

        // Transformer must be executed before ChoiceToValueTransformer so it can transform OrderAddress to a key.
        $builder->addViewTransformer($this->orderAddressToAddressIdentifierViewTransformer, true);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function onSubmit(FormEvent $event): void
    {
        $options = $event->getForm()->getConfig()->getOptions();
        $orderAddress = $this->getSelectedAddress($options['object'], $options['address_type']);

        /** @var CustomerAddress|CustomerUserAddress|string|null $selectedAddress */
        $selectedAddress = $event->getData();

        if ($selectedAddress === null || $selectedAddress === self::ENTER_MANUALLY) {
            if ($orderAddress &&
                !$orderAddress->getCustomerAddress() &&
                !$orderAddress->getCustomerUserAddress()) {
                $event->setData($orderAddress);
            }
        } else {
            $event->setData(null);
            if ($selectedAddress) {
                $event->setData($this->orderAddressManager->updateFromAbstract($selectedAddress, $orderAddress));
            }
        }
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var TypedOrderAddressCollection $collection */
        $collection = $options['address_collection'];
        $addresses = $collection->toArray();

        $action = count($addresses) ? 'select' : 'enter';
        $view->vars['address_count'] = count($addresses);
        $view->vars['label'] = sprintf(
            'oro.checkout.form.address.%s.%s.label.short',
            $action,
            $options['address_type']
        );
        $view->vars['checkout'] = $options['object'];
        $view->vars['checkoutId'] = $options['object']->getId();

        $currentAddress = $form->getData();
        if ($currentAddress instanceof AddressBookAwareInterface &&
            !AddressBookAddressUtils::getAddressBookAddress($currentAddress)) {
            $addresses[self::ENTER_MANUALLY] = $currentAddress;
        }

        $plainAddresses = $this->getPlainData($addresses);

        $view->vars['attr']['data-addresses'] = json_encode($plainAddresses);
        $view->vars['attr']['data-addresses-types'] = json_encode(
            $this->orderAddressManager->getAddressTypes($addresses, 'oro.checkout.')
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['object', 'address_type'])
            ->setDefaults([
                'data_class' => null,
                'label' => false,
                'configs' => [
                    'placeholder' => 'oro.checkout.form.address.choose',
                ],
                'address_collection' => function (Options $options) {
                    return $this->orderAddressManager->getGroupedAddresses(
                        $options['object'],
                        $options['address_type'],
                        'oro.checkout.'
                    );
                },
                'choice_loader' => function (Options $options) {
                    return new CallbackChoiceLoader(function () use ($options) {
                        $collection = $options['address_collection'];
                        $choices = $collection->toArray();

                        $isGranted = $this->orderAddressSecurityProvider->isManualEditGranted($options['address_type']);
                        if ($isGranted) {
                            $choices['oro.checkout.form.address.manual'] = self::ENTER_MANUALLY;
                        }

                        return $choices;
                    });
                },
                'choice_value' => function ($choice) {
                    if (is_scalar($choice)) {
                        return $choice;
                    }

                    if ($choice instanceof CustomerAddress || $choice instanceof CustomerUserAddress) {
                        return $this->orderAddressManager->getIdentifier($choice);
                    }

                    return null;
                },
                'choice_label' => function ($choice, $key) {
                    if ($choice instanceof AbstractAddress) {
                        return $this->addressFormatter->format($choice, null, ', ');
                    }

                    return $key;
                },
            ])
            ->setAllowedValues('address_type', [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING])
            ->setAllowedTypes('address_collection', TypedOrderAddressCollection::class)
            ->setAllowedTypes('object', Checkout::class)
            ->addNormalizer('disabled', function ($options, $value) {
                return $value ?? false;
            });
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_checkout_address_select';
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }

    private function getSelectedAddress(Checkout $entity, string $type): ?OrderAddress
    {
        $address = null;
        if ($type === AddressType::TYPE_BILLING) {
            $address = $entity->getBillingAddress();
        } elseif ($type === AddressType::TYPE_SHIPPING) {
            $address = $entity->getShippingAddress();
        }

        return $address;
    }

    private function getPlainData(array $addresses = []): array
    {
        $data = [];

        array_walk_recursive($addresses, function ($item, $key) use (&$data) {
            if ($item instanceof AbstractAddress) {
                $data[$key] = $this->serializer->normalize($item);
            }
        });

        return $data;
    }
}
