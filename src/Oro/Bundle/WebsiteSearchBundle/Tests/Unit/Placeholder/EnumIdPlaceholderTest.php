<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

class EnumIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EnumIdPlaceholder */
    protected $placeholder;

    protected function setUp(): void
    {
        $this->placeholder = new EnumIdPlaceholder();
    }

    public function testGetPlaceholder()
    {
        $this->assertIsString($this->placeholder->getPlaceholder());
        $this->assertEquals(EnumIdPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefault()
    {
        $this->assertEquals(
            'string_',
            $this->placeholder->replaceDefault('string_' . EnumIdPlaceholder::NAME)
        );
    }

    public function testReplace()
    {
        $this->assertEquals(
            'string_42',
            $this->placeholder->replace('string_' . EnumIdPlaceholder::NAME, [EnumIdPlaceholder::NAME => 42])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->assertEquals(
            'string_' . EnumIdPlaceholder::NAME,
            $this->placeholder->replace('string_' . EnumIdPlaceholder::NAME, ['NOT_' . EnumIdPlaceholder::NAME => 1])
        );
    }
}
