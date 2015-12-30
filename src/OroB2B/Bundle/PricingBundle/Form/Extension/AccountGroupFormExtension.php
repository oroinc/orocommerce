<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\PricingBundle\Form\Type\AccountGroupWebsiteScopedPriceListsType;

class AccountGroupFormExtension extends AbstractTypeExtension
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
        return AccountGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'priceListsByWebsites',
                AccountGroupWebsiteScopedPriceListsType::NAME
            )
            ->add(
                'fallback',
                'choice',
                [
                    'label' => 'orob2b.pricing.fallback.label',
                    'mapped' => false,
                    'choices' => [
                        PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                            'orob2b.pricing.fallback.current_account_group_only.label',
                        PriceListAccountGroupFallback::WEBSITE =>
                            'orob2b.pricing.fallback.website.label',
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
        /** @var AccountGroup|null $accountGroup */
        $accountGroup = $event->getData();
        if (!$accountGroup || !$accountGroup->getId()) {
            return;
        }
        $fallback = $this->getFallback($accountGroup);
        $fallbackField = $event->getForm()->get('fallback');
        if (!$fallback || $fallback->getFallback() === PriceListAccountGroupFallback::WEBSITE) {
            $fallbackField->setData(PriceListAccountGroupFallback::WEBSITE);
        } else {
            $fallbackField->setData(PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY);
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
        /** @var AccountGroup|null $accountGroup */
        $accountGroup = $event->getData();
        if (!$accountGroup || !$accountGroup->getId()) {
            return;
        }
        /** @var PriceListAccountGroupFallback $fallback */
        $fallback = $this->getFallback($accountGroup);
        if (!$fallback) {
            $fallback = new PriceListAccountGroupFallback();
            $this->registry
                ->getManagerForClass('OroB2BPricingBundle:PriceListAccountGroupFallback')
                ->persist($fallback);
        }
        $fallback->setAccountGroup($accountGroup);
        $fallback->setFallback($form->get('fallback')->getData());
    }

    /**
     * @param AccountGroup $accountGroup
     * @return null|PriceListAccountGroupFallback
     */
    protected function getFallback(AccountGroup $accountGroup)
    {
        /** @var PriceListAccountGroupFallback $fallback */
        return $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->findOneBy(['accountGroup' => $accountGroup]);
    }
}
