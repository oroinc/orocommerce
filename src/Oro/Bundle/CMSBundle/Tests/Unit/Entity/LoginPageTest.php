<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LoginPageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['topContent', 'Some <b>html</b>'],
            ['bottomContent', 'Other <b>html</b>'],
            ['css', 'css styles {}'],
        ];

        $loginPage = new LoginPage();

        $this->assertPropertyAccessors($loginPage, $properties);
    }
}
