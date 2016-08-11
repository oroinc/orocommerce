<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class LocalizationIdPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationIdPlaceholder */
    private $placeholder;

    protected function setUp()
    {
        $this->placeholder = new LocalizationIdPlaceholder();
    }

    protected function tearDown()
    {
        unset($this->placeholder);
    }

    public function testGetPlaceholder()
    {
        $this->assertInternalType('string', $this->placeholder->getPlaceholder());
        $this->assertEquals('LOCALIZATION_ID', $this->placeholder->getPlaceholder());
    }

    public function testGetValue()
    {
        $this->assertInternalType('string', $this->placeholder->getValue());
        $this->assertEquals('', $this->placeholder->getValue());
    }
}
