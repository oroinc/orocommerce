<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\AccountBundle\Entity\AccountUserSettings;

class AccountUserSettingsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new AccountUserSettings(new Website()), [
            ['accountUser', new AccountUser()],
            ['currency', 'some string']
        ]);
    }
}
