<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerType;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class CustomerFormExtension extends AbstractPriceListExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Customer|null $customer */
        $customer = $event->getData();
        if (!$customer) {
            return null;
        }

        $priceList = $this->getPriceListRepository()->getPriceListByCustomer($customer);

        $event->getForm()->get('priceList')->setData($priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Customer|null $customer */
        $customer = $event->getData();
        if (!$customer) {
            return null;
        }

        /** @var PriceList|null $customer */
        $priceList = $event->getForm()->get('priceList')->getData();

        $this->getPriceListRepository()->setPriceListToCustomer($customer, $priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerType::NAME;
    }
}
