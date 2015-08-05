<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class AccountGroupFormExtension extends AbstractPriceListExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var AccountGroup|null $accountGroup */
        $accountGroup = $event->getData();
        if (!$accountGroup || !$accountGroup->getId()) {
            return;
        }

        $priceList = $this->getPriceListRepository()->getPriceListByAccountGroup($accountGroup);

        $event->getForm()->get('priceList')->setData($priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var AccountGroup|null $accountGroup */
        $accountGroup = $event->getData();
        if (!$accountGroup || !$accountGroup->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var PriceList|null $priceList */
        $priceList = $form->get('priceList')->getData();

        $this->getPriceListRepository()->setPriceListToAccountGroup($accountGroup, $priceList);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountGroupType::NAME;
    }
}
