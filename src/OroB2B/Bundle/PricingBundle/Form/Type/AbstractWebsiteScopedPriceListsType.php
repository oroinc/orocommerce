<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

abstract class AbstractWebsiteScopedPriceListsType extends AbstractType
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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultWebsite = $this->registry
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getDefaultWebsite();

        $resolver->setDefaults(
            [
                'type' => PriceListCollectionType::NAME,
                'label' => 'orob2b.pricing.pricelist.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'ownership_disabled' => true,
                'data' => [],
                'preloaded_websites' => [],
            ]
        );
    }

    public function getParent()
    {
        return WebsiteScopedDataType::NAME;
    }

    /**
     * @param FormEvent $event
     */
    abstract public function onPostSetData(FormEvent $event);

    /**
     * @param FormEvent $event
     */
    abstract public function onPostSubmit(FormEvent $event);
}
