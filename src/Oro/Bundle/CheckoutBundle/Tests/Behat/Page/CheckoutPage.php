<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Page;

use Behat\Behat\Tester\Exception\PendingException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class CheckoutPage extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        throw new PendingException();
    }
}
