<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Model;

use OroB2B\Bundle\AttributeBundle\Model\SharingType;

class SharingTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructAndGetType()
    {
        $this->assertEquals(
            [SharingType::GENERAL, SharingType::GROUP, SharingType::WEBSITE],
            SharingType::getTypes()
        );
    }
}
