<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;

class LoginPageTest extends \PHPUnit_Framework_TestCase
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
