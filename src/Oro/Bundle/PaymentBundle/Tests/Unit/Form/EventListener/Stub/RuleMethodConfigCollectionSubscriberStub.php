<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventListener\Stub;

use Oro\Bundle\PaymentBundle\Form\EventSubscriber\RuleMethodConfigCollectionSubscriber;

class RuleMethodConfigCollectionSubscriberStub extends RuleMethodConfigCollectionSubscriber
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
