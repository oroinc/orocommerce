<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

class PlaceholderValueTest extends \PHPUnit_Framework_TestCase
{
    public function testPlaceholderValue()
    {
        $value = new PlaceholderValue('PLACEHOLDER', ['PLACEHOLDER' => 'value']);
        $this->assertEquals('PLACEHOLDER', $value->getValue());
        $this->assertEquals(['PLACEHOLDER' => 'value'], $value->getPlaceholders());
    }
}
