<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ConsentAcceptanceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['consent', new Consent()],
            ['landingPage', new Page()],
            ['createdAt', $now, false]
        ];

        $this->assertPropertyAccessors(new ConsentAcceptance(), $properties);
    }
}
