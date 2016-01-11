<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceListsSettingsType extends AbstractType
{
    /** @var  Registry */
    protected $registry;

    const NAME = 'orob2b_pricing_price_lists_settings';

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }


    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
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
        $builder->add(
            'price_list_collection',
            PriceListCollectionType::NAME,
            ['label' => 'orob2b.pricing.pricelist.entity_plural_label']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'render_as_widget' => true,
                'website' => null,
                'label' => false,
            ]
        );
        $resolver->setRequired(['target_class_name']);
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
        $fallback = $this->getFallback($account);
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
    protected function getFallback($account)
    {
        /** @var PriceListAccountFallback $fallback */
        return $this->registry
            ->getManagerForClass('OroB2BPricingBundle:PriceListAccountFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->findOneBy(['account' => $account]);
    }
}
