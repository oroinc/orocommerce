<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\PricingBundle\Form\Type\AccountWebsiteScopedPriceListsType;

class AccountFormExtension extends AbstractTypeExtension
{
    /** @var  Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'priceListsByWebsites',
                AccountWebsiteScopedPriceListsType::NAME
            )
            ->add(
                'fallback',
                'choice',
                [
                    'label' => 'orob2b.pricing.fallback.label',
                    'mapped' => false,
                    'choices' => [
                        PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
                            'orob2b.pricing.fallback.current_account_only.label',
                        PriceListAccountFallback::ACCOUNT_GROUP =>
                            'orob2b.pricing.fallback.account_group.label',
                    ],
                ]
            );
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account || !$account->getId()) {
            return;
        }
        $fallback= $this->getFallback($account);
        $fallbackField = $event->getForm()->get('fallback');
        if (!$fallback || $fallback->getFallback() === PriceListAccountFallback::ACCOUNT_GROUP) {
            $fallbackField->setData(PriceListAccountFallback::ACCOUNT_GROUP);
        } else {
            $fallbackField->setData(PriceListAccountFallback::CURRENT_ACCOUNT_ONLY);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }
        /** @var Account|null $account */
        $account = $event->getData();
        if (!$account || !$account->getId()) {
            return;
        }
        $fallback = $this->getFallback($account);
        if (!$fallback) {
            $fallback = new PriceListAccountFallback();
            $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountFallback')->persist($fallback);
        }
        $fallback->setAccount($account);
        $fallback->setFallback($form->get('fallback')->getData());
    }

    /**
     * @param Account $account
     * @return null|PriceListAccountFallback
     */
    protected function getFallback(Account $account)
    {
        /** @var PriceListAccountFallback $fallback */
        return $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListAccountFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->findOneBy(['account' => $account]);
    }
}
