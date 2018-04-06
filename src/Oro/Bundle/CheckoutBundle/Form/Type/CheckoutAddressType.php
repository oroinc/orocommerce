<?php

namespace Oro\Bundle\CheckoutBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AddressType as AddressTypeEntity;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\FrontendBundle\Form\Type\CountryType;
use Oro\Bundle\FrontendBundle\Form\Type\RegionType;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CheckoutAddressType extends AbstractOrderAddressType
{
    const NAME = 'oro_checkout_address';
    const ENTER_MANUALLY = 0;
    const SHIPPING_ADDRESS_NAME = 'shipping_address';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $event->setData(
                    $this->clearCustomFields(
                        $event->getData()
                    )
                );
            }
        );
    }

    /**
     * @param array|null $data
     * @return array
     */
    private function clearCustomFields($data)
    {
        if (isset($data['customerAddress']) && $data['customerAddress']) {
            return [
                'customerAddress' => $data['customerAddress']
            ];
        }

        return $data;
    }

    /**
     * @param Checkout $entity
     * {@inheritdoc}
     */
    protected function initCustomerAddressField(
        FormBuilderInterface $builder,
        $type,
        CustomerOwnerAwareInterface $entity,
        $isManualEditGranted,
        $isEditEnabled
    ) {
        if ($isEditEnabled) {
            $addressCollection = $this->orderAddressManager->getGroupedAddresses($entity, $type, 'oro.checkout.');
            $defaultKey = $addressCollection->getDefaultAddressKey();
            $selectedKey = $this->getSelectedAddress($entity, $type);
            if (null === $selectedKey) {
                $selectedKey = $defaultKey;
            }

            $addresses = $addressCollection->toArray();
            $action = count($addresses) ? 'select' : 'enter';

            $customerAddressOptions = [
                'label' => sprintf('oro.checkout.form.address.%s.%s.label', $action, $type),
                'required' => true,
                'mapped' => false,
                'choices' => $this->getChoices($addresses),
                'attr' => [
                    'data-addresses' => json_encode($this->getPlainData($addresses)),
                    'data-addresses-types' => json_encode(
                        $this->orderAddressManager->getAddressTypes($addresses, 'oro.checkout.')
                    ),
                    'data-default' => $defaultKey,
                ],
                'data' => $selectedKey
            ];

            if ($isManualEditGranted) {
                $customerAddressOptions['choices'] = array_merge(
                    $customerAddressOptions['choices'],
                    [self::ENTER_MANUALLY => 'oro.checkout.form.address.manual']
                );
            }
            $builder
                ->add('customerAddress', 'choice', $customerAddressOptions)
                ->add('country', CountryType::class, ['required' => true, 'label' => 'oro.address.country.label'])
                ->add('region', RegionType::class, ['required' => true, 'label' => 'oro.address.region.label']);

            $builder->get('city')->setRequired(true);
            $builder->get('postalCode')->setRequired(true);
            $builder->get('street')->setRequired(true);
        }
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
     * {@inheritdoc}
     */
    public function getParent()
    {
        return AddressType::class;
    }

    /**
     * @param object $entity
     * @param string $type
     * @return null|string
     */
    protected function getSelectedAddress($entity, $type)
    {
        $selectedKey = null;
        if ($type === AddressTypeEntity::TYPE_BILLING) {
            $selectedKey = $this->getSelectedAddressKey($entity->getBillingAddress());
        } elseif ($type === AddressTypeEntity::TYPE_SHIPPING) {
            $selectedKey = $this->getSelectedAddressKey($entity->getShippingAddress());
        }

        return $selectedKey;
    }

    /**
     * @param OrderAddress|null $checkoutAddress
     * @return int|string
     */
    protected function getSelectedAddressKey(OrderAddress $checkoutAddress = null)
    {
        $selectedKey = null;
        if ($checkoutAddress) {
            $selectedKey = self::ENTER_MANUALLY;
            if ($checkoutAddress->getCustomerAddress()) {
                $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getCustomerAddress());
            } elseif ($checkoutAddress->getCustomerUserAddress()) {
                $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getCustomerUserAddress());
            }
        }

        return $selectedKey;
    }
}
