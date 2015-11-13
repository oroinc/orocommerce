<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

abstract class AbstractPriceListExtension extends AbstractTypeExtension
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultWebsite = $this->registry
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getDefaultWebsite();
        $builder->add(
            'priceListsByWebsites',
            WebsiteScopedDataType::NAME,
            [
                'type' => PriceListCollectionType::NAME,
                'label' => 'orob2b.pricing.pricelist.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'ownership_disabled' => true,
                'data' => [[new PriceListToAccount()]],
                'preloaded_websites' => [$defaultWebsite],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * @param FormEvent $event
     */
    abstract public function onPostSetData(FormEvent $event);

    /**
     * @param FormEvent $event
     */
    abstract public function onPostSubmit(FormEvent $event);

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        return $this->registry->getManagerForClass('OroB2BPricingBundle:PriceList')
            ->getRepository('OroB2BPricingBundle:PriceList');
    }
}
