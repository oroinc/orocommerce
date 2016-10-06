<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Renderer;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashMessageRenderer implements RendererInterface
{
    const DEFAULT_STATUS = 'info';

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var array
     */
    private static $statusMap = [
        Message::STATUS_ERROR => 'error',
        Message::STATUS_INFO => 'info',
        Message::STATUS_SUCCESS => 'success',
        Message::STATUS_WARNING => 'warning',
    ];

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
        if (array_key_exists($message->getStatus(), self::$statusMap)) {
            $status = self::$statusMap[$message->getStatus()];
        } else {
            $status = self::DEFAULT_STATUS;
        }

        $this->flashBag->add($status, $message->getMessage());
    }
}
