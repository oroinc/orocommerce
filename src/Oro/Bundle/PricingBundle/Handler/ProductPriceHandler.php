<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Handles form submission and persistence of product prices.
 *
 * Extends the base form handler to provide specialized handling for product price data,
 * including custom persistence logic through the PriceManager.
 */
class ProductPriceHandler extends FormHandler
{
    /**
     * @var PriceManager
     */
    protected $priceManager;

    /**
     * ProductPriceHandler constructor.
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        PriceManager $priceManager
    ) {
        parent::__construct($eventDispatcher, $doctrineHelper);
        $this->priceManager = $priceManager;
    }

    #[\Override]
    protected function saveData($data, FormInterface $form)
    {
        $this->priceManager->persist($data);
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::BEFORE_FLUSH);
        $this->priceManager->flush();
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::AFTER_FLUSH);
    }
}
