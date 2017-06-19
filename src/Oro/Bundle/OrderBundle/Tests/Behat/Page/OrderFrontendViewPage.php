<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Page;

use Behat\Behat\Tester\Exception\PendingException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class OrderFrontendViewPage extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        throw new PendingException();
    }
}
