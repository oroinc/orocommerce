<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class AccountFormExtension extends AbstractPriceListExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account || !$account->getId()) {
            return;
        }

        $priceList = $this->getPriceListRepository()->getPriceListByAccount($account);

        $event->getForm()->get('priceList')->setData($priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account || !$account->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PriceList|null $priceList */
        $priceList = $form->get('priceList')->getData();

        $this->getPriceListRepository()->setPriceListToAccount($account, $priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountType::NAME;
    }
}
