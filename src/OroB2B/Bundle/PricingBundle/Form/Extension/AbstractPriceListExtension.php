<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

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
        $builder->add(
            'priceList',
            PriceListSelectType::NAME,
            [
                'label' => 'orob2b.pricing.pricelist.entity_label',
                'required' => false,
                'mapped' => false,
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
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
