<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType;

class CheckoutAddressType extends AbstractOrderAddressType
{
    const NAME = 'orob2b_checkout_address';

    /**
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
            if (!$selectedKey) {
                $selectedKey = $defaultKey;
            }

            $accountAddressOptions = [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'choices' => $this->getChoices($addresses),
                'attr' => [
                    'data-addresses' => json_encode($this->getPlainData($addresses)),
                    'data-default' => $defaultKey,
                ],
                'data' => $selectedKey
            ];

            if ($isManualEditGranted) {
                $accountAddressOptions['choices'] = array_merge(
                    $accountAddressOptions['choices'],
                    ['orob2b.order.form.address.manual']
                );
            }
            $builder
                ->add('accountAddress', 'choice', $accountAddressOptions)
                ->add('country', 'orob2b_country', ['required' => true, 'label' => 'oro.address.country.label'])
                ->add('region', 'orob2b_region', ['required' => false, 'label' => 'oro.address.region.label']);
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
     * @param Checkout $entity
     * @param string $type
     * @return null|string
     */
    protected function getSelectedAddress(Checkout $entity, $type)
    {
        $selectedKey = null;
        if ($type == AddressType::TYPE_BILLING) {
            $checkoutAddress = $entity->getBillingAddress();
            if ($checkoutAddress) {
                if ($checkoutAddress->getAccountAddress()) {
                    $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getAccountAddress());
                }
                if ($entity->getBillingAddress()->getAccountUserAddress()) {
                    $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getAccountUserAddress());
                }
            }
        } elseif ($type == AddressType::TYPE_SHIPPING) {
            $checkoutAddress = $entity->getShippingAddress();
            if ($checkoutAddress) {
                if ($checkoutAddress->getAccountAddress()) {
                    $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getAccountAddress());
                }
                if ($checkoutAddress->getAccountUserAddress()) {
                    $selectedKey = $this->orderAddressManager->getIdentifier($checkoutAddress->getAccountUserAddress());
                }
            }
        }

        return $selectedKey;
    }
}
