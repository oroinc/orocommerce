<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Renderer;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;

interface RendererInterface
{
    /**
     * @param Message $message
     */
    public function render(Message $message);
}
