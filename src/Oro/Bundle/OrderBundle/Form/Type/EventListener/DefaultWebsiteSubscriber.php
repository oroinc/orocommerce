<?php

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultWebsiteSubscriber implements EventSubscriberInterface
{
    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => ['onSubmitEventListener', 10]
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmitEventListener(FormEvent $event)
    {
        $data = $event->getData();

        if (!$data instanceof Order) {
            return;
        }

        if ($data->getWebsite()) {
            return;
        }

        $data->setWebsite($this->websiteManager->getDefaultWebsite());
        $event->setData($data);
    }
}
