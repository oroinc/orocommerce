<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class CustomerGroupFormExtension extends AbstractPriceListExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var CustomerGroup|null $customerGroup */
        $customerGroup = $event->getData();
        if (!$customerGroup || !$customerGroup->getId()) {
            return;
        }

        $priceList = $this->getPriceListRepository()->getPriceListByCustomerGroup($customerGroup);

        $event->getForm()->get('priceList')->setData($priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var CustomerGroup|null $customerGroup */
        $customerGroup = $event->getData();
        if (!$customerGroup || !$customerGroup->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PriceList|null $customer */
        $priceList = $form->get('priceList')->getData();

        $this->getPriceListRepository()->setPriceListToCustomerGroup($customerGroup, $priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerGroupType::NAME;
    }
}
