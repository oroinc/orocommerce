<?php

namespace Oro\Bundle\RFPBundle\Layout\Decider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\RFPBundle\Event\FormSubmitCheckEvent;

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

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param RequestStack $requestStack
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, RequestStack $requestStack)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $requestStack->getCurrentRequest();
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
        $this->eventDispatcher->dispatch($eventName, $event);

        return $event->isSubmitOnError();
    }
}
