<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountWebsiteScopedPriceListsType extends AbstractWebsiteScopedPriceListsType
{
    const NAME = 'orob2b_pricing_account_website_scoped_price_lists';

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

//        $priceLists[] = $this->getPriceListRepository()->getPriceListByAccount($account);
        $priceLists = [];
        $event->setData($priceLists);
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

        /** @var FormInterface[] $priceListsByWebsites */
        $priceListsByWebsites = $form->get('priceListsByWebsites');

        foreach ($priceListsByWebsites as $priceListsByWebsite) {
            $website = $priceListsByWebsite->getConfig()->getOption('website');
            foreach ($priceListsByWebsite as $priceList) {
                $pl = $priceList;
                $acc = $account;
                $ws = $website;
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


    protected function updatePriceListRelations($priceList, $account, $website)
    {
        $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
            ->getRepository('OroB2BPricingBundle:PriceListToAccount');

    }
}
