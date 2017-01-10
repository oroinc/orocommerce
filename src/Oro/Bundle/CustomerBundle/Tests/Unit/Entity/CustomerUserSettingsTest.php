<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;

class CustomerUserSettingsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new CustomerUserSettings(new Website()), [
            ['customerUser', new CustomerUser()],
            ['currency', 'some string']
        ]);
    }
}
