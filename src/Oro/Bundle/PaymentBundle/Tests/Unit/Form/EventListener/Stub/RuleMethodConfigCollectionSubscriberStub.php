<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventListener\Stub;

use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;

class RuleMethodConfigCollectionSubscriberStub extends RuleMethodConfigCollectionSubscriber
{
    public function __construct()
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
