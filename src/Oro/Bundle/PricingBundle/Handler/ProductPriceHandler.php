<?php

namespace Oro\Bundle\PricingBundle\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Handler\FormHandler;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;

class ProductPriceHandler extends FormHandler
{
    /**
     * @var PriceManager
     */
    protected $priceManager;

    /**
     * ProductPriceHandler constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param PriceManager $priceManager
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        PriceManager $priceManager
    ) {
        parent::__construct($eventDispatcher, $doctrineHelper);
        $this->priceManager = $priceManager;
    }

    /**
     * @param $data
     * @param FormInterface $form
     */
    protected function saveData($data, FormInterface $form)
    {
        $this->priceManager->persist($data);
        $this->eventDispatcher->dispatch(Events::BEFORE_FLUSH, new AfterFormProcessEvent($form, $data));
        $this->priceManager->flush();
        $this->eventDispatcher->dispatch(Events::AFTER_FLUSH, new AfterFormProcessEvent($form, $data));
    }
}
