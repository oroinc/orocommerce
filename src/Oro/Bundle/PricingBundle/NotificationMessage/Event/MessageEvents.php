<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Event;

final class MessageEvents
{
    const BEFORE_SEND = 'notification_message.before_send';
    const AFTER_SEND = 'notification_message.after_send';
    const ON_RECEIVE = 'notification_message.on_receive';
    const ON_REMOVE = 'notification_message.on_remove';
}
