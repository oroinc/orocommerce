<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

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

    protected function saveData($data, FormInterface $form)
    {
        $this->priceManager->persist($data);
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::BEFORE_FLUSH);
        $this->priceManager->flush();
        $this->eventDispatcher->dispatch(new AfterFormProcessEvent($form, $data), Events::AFTER_FLUSH);
    }
}
