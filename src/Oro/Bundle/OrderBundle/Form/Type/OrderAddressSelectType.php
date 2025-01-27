<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents order address select form type with list of grouped available addresses for customer user
 */
class OrderAddressSelectType extends AbstractType
{
    public const int ENTER_MANUALLY = 0;

    public function __construct(
        private OrderAddressManager $orderAddressManager,
        private AddressFormatter $addressFormatter,
        private OrderAddressSecurityProvider $orderAddressSecurityProvider,
        private Serializer $serializer
    ) {
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var TypedOrderAddressCollection $collection */
        $collection = $options['address_collection'];
        $addresses = $this->getPlainData($collection->toArray());

        $view->vars['attr']['data-addresses'] = json_encode($addresses);
        $view->vars['attr']['data-default'] = $collection->getDefaultAddressKey();
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['order', 'address_type'])
            ->setDefaults([
                'data_class' => null,
                'label' => false,
                'configs' => [
                    'placeholder' => 'oro.order.form.address.choose',
                ],
                'address_collection' => function (Options $options) {
                    return $this->orderAddressManager
                        ->getGroupedAddresses($options['order'], $options['address_type'], 'oro.order.');
                },
                'choice_loader' => function (Options $options) {
                    return new CallbackChoiceLoader(function () use ($options) {
                        $collection = $options['address_collection'];
                        $choices = $collection->toArray();

                        $isGranted = $this->orderAddressSecurityProvider->isManualEditGranted($options['address_type']);
                        if ($isGranted) {
                            $choices['oro.order.form.address.manual'] = self::ENTER_MANUALLY;
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
            ->setAllowedTypes('order', CustomerOwnerAwareInterface::class);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_address_select';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
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
