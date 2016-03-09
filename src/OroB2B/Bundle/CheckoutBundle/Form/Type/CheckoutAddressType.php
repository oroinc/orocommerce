<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;
use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;

class CheckoutAddressType extends OrderAddressType
{
    const NAME = 'orob2b_checkout_address_type';

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

            $accountAddressOptions = [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'choices' => $this->getChoices($addresses),
                'attr' => [
                    'data-addresses' => json_encode($this->getPlainData($addresses)),
                    'data-default' => $this->getDefaultAddressKey($entity, $type, $addresses),
                ],
            ];

            if ($isManualEditGranted) {
                $accountAddressOptions['choices'] = array_merge(
                    $accountAddressOptions['choices'],
                    ['orob2b.order.form.address.manual']
                );
            }

            $builder->add('accountAddress', 'choice', $accountAddressOptions);
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}
