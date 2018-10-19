<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Model;

use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PrefixWithRedirectTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new PrefixWithRedirect(), [
            ['prefix', 'some-prefix'],
            ['createRedirect', true]
        ]);
    }
}
