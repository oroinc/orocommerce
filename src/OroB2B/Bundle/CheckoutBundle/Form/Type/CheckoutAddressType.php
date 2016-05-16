<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType;

class CheckoutAddressType extends AbstractOrderAddressType
{
    const NAME = 'orob2b_checkout_address';
    const ENTER_MANUALLY = 0;
    const SHIPPING_ADDRESS_NAME = 'shipping_address';

    /**
     * @param Checkout $entity
     * {@inheritdoc}
     */
    protected function initAccountAddressField(
        FormBuilderInterface $builder,
        $type,
        AccountOwnerAwareInterface $entity,
        $isManualEditGranted,
        $isEditEnabled
    ) {
        if ($isEditEnabled) {
            $addresses = $this->orderAddressManager->getGroupedAddresses($entity, $type);
            $defaultKey = $this->getDefaultAddressKey($entity, $type, $addresses);
            $selectedKey = $this->getSelectedAddress($entity, $type);
            if (null === $selectedKey) {
                $selectedKey = $defaultKey;
            }

            $accountAddressOptions = [
                'label' => sprintf('orob2b.checkout.form.address.select.%s.label', $type),
                'required' => true,
                'mapped' => false,
                'choices' => $this->getChoices($addresses),
                'attr' => [
                    'data-addresses' => json_encode($this->getPlainData($addresses)),
                    'data-addresses-types' => json_encode($this->orderAddressManager->getAddressTypes($addresses)),
                    'data-default' => $defaultKey,
                ],
                'data' => $selectedKey
            ];

            if ($isManualEditGranted) {
                $accountAddressOptions['choices'] = array_merge(
                    $accountAddressOptions['choices'],
                    [self::ENTER_MANUALLY => 'orob2b.checkout.form.address.manual']
                );
            }
            $builder
                ->add('accountAddress', 'choice', $accountAddressOptions)
                ->add('country', 'orob2b_country', ['required' => true, 'label' => 'oro.address.country.label'])
                ->add('region', 'orob2b_region', ['required' => false, 'label' => 'oro.address.region.label']);

            if ($type === AddressType::TYPE_BILLING) {
                $builder->get('firstName')->setRequired(true);
                $builder->get('lastName')->setRequired(true);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_address';
    }

    /**
     * @param object $entity
     * @param string $type
     * @return null|string
     */
    protected function getSelectedAddress($entity, $type)
    {
        $selectedKey = null;
        if ($type === AddressType::TYPE_BILLING) {
            $selectedKey = $this->getSelectedAddressKey($entity->getBillingAddress());
        } elseif ($type === AddressType::TYPE_SHIPPING) {
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
            if ($checkoutAddress->getAccountAddress()) {
                $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getAccountAddress());
            } elseif ($checkoutAddress->getAccountUserAddress()) {
                $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getAccountUserAddress());
            }
        }

        return $selectedKey;
    }
}
