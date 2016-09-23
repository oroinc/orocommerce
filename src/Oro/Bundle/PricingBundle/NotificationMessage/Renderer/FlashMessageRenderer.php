<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Renderer;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashMessageRenderer implements RendererInterface
{
    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @param FlashBagInterface $flashBag
     */
    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /**
     * @param Message $message
     */
    public function render(Message $message)
    {
        $this->flashBag->add($message->getStatus(), $message->getMessage());
    }
}
