<?php

namespace Oro\Bundle\RFPBundle\Layout\Decider;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RFPActionDecider
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(EventDispatcherInterface $eventDispatcher, RequestStack $requestStack)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $requestStack->getMasterRequest();
    }

    /**
     * @return bool
     */
    public function shouldFormSubmitWithErrors()
    {
        $eventName = sprintf('%s.%s', FormSubmitCheckEvent::NAME, $this->request->get('_route'));
        if (!$this->eventDispatcher->hasListeners($eventName)) {
            return false;
        }

        $event = new FormSubmitCheckEvent();
        $this->eventDispatcher->dispatch($event, $eventName);

        return $event->isSubmitOnError();
    }
}
