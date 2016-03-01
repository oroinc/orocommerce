<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\PricingBundle\Form\Extension\WebsiteFormExtension;
use OroB2B\Bundle\PricingBundle\Form\PriceListWithPriorityCollectionHandler;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteListener
{
    /**
     * @var PriceListWithPriorityCollectionHandler
     */
    protected $priceListCollectionHandler;

    /**
     * @var string
     */
    protected $priceListToWebsiteClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param PriceListWithPriorityCollectionHandler $priceListCollectionHandler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PriceListWithPriorityCollectionHandler $priceListCollectionHandler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->priceListCollectionHandler = $priceListCollectionHandler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeFlush(AfterFormProcessEvent $event)
    {
        /** @var Website $website */
        $website = $event->getData();
        $submittedPriceLists = (array)$event->getForm()
            ->get(WebsiteFormExtension::PRICE_LISTS_TO_WEBSITE_FIELD)
            ->getData();

        $existing = $this->doctrineHelper
            ->getEntityRepository($this->priceListToWebsiteClass)
            ->findBy(['website' => $website], ['priority' => PriceListCollectionType::DEFAULT_ORDER]);

        $this->priceListCollectionHandler->handleChanges($submittedPriceLists, $existing, $website);
    }
}
