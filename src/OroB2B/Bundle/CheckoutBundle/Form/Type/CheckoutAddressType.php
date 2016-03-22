<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType;
use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;

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
            $accountAddressOptions = [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'choices' => $this->getChoices($addresses),
                'attr' => [
                    'data-addresses' => json_encode($this->getPlainData($addresses)),
                    'data-addresses-types' => json_encode($this->orderAddressManager->getAddressTypes($addresses)),
                    'data-default' => $defaultKey,
                ],
                'data' => $defaultKey
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
}
