<?php

namespace Oro\Bundle\ProductBundle\Tests\Behat\Page;

use Behat\Behat\Tester\Exception\PendingException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class QuickOrder extends Page
{
    #[\Override]
    public function open(array $parameters = [])
    {
        throw new PendingException();
    }
}
