<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserSettings;

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
