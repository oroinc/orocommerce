<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Renderer;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;

interface RendererInterface
{
    public function render(Message $message);
}
