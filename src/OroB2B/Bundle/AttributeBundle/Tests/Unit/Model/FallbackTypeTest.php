<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Model;

use OroB2B\Bundle\AttributeBundle\Model\FallbackType;

class FallbackTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndGetType()
    {
        $type = FallbackType::SYSTEM;
        $fallbackType = new FallbackType($type);

        $this->assertAttributeEquals($type, 'type', $fallbackType);
        $this->assertEquals($type, $fallbackType->getType());
    }
}
