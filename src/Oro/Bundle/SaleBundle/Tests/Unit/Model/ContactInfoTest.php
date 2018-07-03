<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model;

use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContactInfoTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            'name' => 'test name',
            'email' => 'asd@asd.dev',
            'phone' => '123',
            'manualText' => 'text',
        ];

        $contactInfo = new ContactInfo();
        static::assertTrue($contactInfo->isEmpty());
        foreach ($properties as $key => $value) {
            $methodName = 'set' . $key;
            $contactInfo->$methodName($value);
            $methodName = 'get' . $key;
            static::assertEquals($value, $contactInfo->$methodName());
        }
        static::assertFalse($contactInfo->isEmpty());
    }
}
