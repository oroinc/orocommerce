<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignTypePlaceholder;

class AssignTypePlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AssignTypePlaceholder
     */
    private $placeholder;

    protected function setUp(): void
    {
        $this->placeholder = new AssignTypePlaceholder();
    }

    public function testGetPlaceholder()
    {
        $this->assertIsString($this->placeholder->getPlaceholder());
        $this->assertEquals('ASSIGN_TYPE', $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefault()
    {
        $this->assertEquals(
            'string_',
            $this->placeholder->replaceDefault('string_ASSIGN_TYPE')
        );
    }

    public function testReplace()
    {
        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_ASSIGN_TYPE', ['ASSIGN_TYPE' => 1])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->assertEquals(
            'string_ASSIGN_TYPE',
            $this->placeholder->replace('string_ASSIGN_TYPE', ['NOT_ASSIGN_TYPE' => 1])
        );
    }
}
