<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class RedirectTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Redirect(), [
            ['id', 42],
            ['from', 'url/from'],
            ['to', 'url/to'],
            ['type', 301]
        ]);
    }
}
