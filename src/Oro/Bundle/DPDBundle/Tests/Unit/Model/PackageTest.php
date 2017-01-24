<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PackageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            new Package(),
            [
                ['weight', 1.0],
                ['contents', 'contents string'],
            ]
        );
    }
}
