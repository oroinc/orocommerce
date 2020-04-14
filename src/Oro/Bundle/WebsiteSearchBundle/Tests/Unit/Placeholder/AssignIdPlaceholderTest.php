<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;

class AssignIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AssignIdPlaceholder
     */
    private $placeholder;

    protected function setUp(): void
    {
        $this->placeholder = new AssignIdPlaceholder();
    }

    public function testGetPlaceholder()
    {
        $this->assertIsString($this->placeholder->getPlaceholder());
        $this->assertEquals('ASSIGN_ID', $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefault()
    {
        $this->assertEquals(
            'string_',
            $this->placeholder->replaceDefault('string_ASSIGN_ID')
        );
    }

    public function testReplace()
    {
        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_ASSIGN_ID', ['ASSIGN_ID' => 1])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->assertEquals(
            'string_ASSIGN_ID',
            $this->placeholder->replace('string_ASSIGN_ID', ['NOT_ASSIGN_ID' => 1])
        );
    }
}
