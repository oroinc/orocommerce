<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderDecorator;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

class PlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var PlaceholderDecorator */
    protected $placeholder;

    /** @var PlaceholderRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock(PlaceholderRegistry::class);

        $this->placeholder = new PlaceholderDecorator($this->registry);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetPlaceholderNotIntendedToRun()
    {
        $this->placeholder->getPlaceholder();
    }

    public function testReplace()
    {
        $placeholder1 = $this->getMock(PlaceholderInterface::class);
        $placeholder1->expects($this->once())->method('replace')
            ->with(
                'string_PLACEHOLDER1_PLACEHOLDER2',
                ['PLACEHOLDER1' => 'value1', 'PLACEHOLDER2' => 'value2']
            )
            ->willReturn('string_value1_PLACEHOLDER2');

        $placeholder2 = $this->getMock(PlaceholderInterface::class);
        $placeholder2->expects($this->once())->method('replace')
            ->with(
                'string_value1_PLACEHOLDER2',
                ['PLACEHOLDER1' => 'value1', 'PLACEHOLDER2' => 'value2']
            )
            ->willReturn('string_value1_value2');

        $this->registry->expects($this->once())->method('getPlaceholders')->willReturn([$placeholder1, $placeholder2]);

        $this->assertEquals(
            'string_value1_value2',
            $this->placeholder->replace(
                'string_PLACEHOLDER1_PLACEHOLDER2',
                ['PLACEHOLDER1' => 'value1', 'PLACEHOLDER2' => 'value2']
            )
        );
    }

    public function testReplaceDefault()
    {
        $placeholder1 = $this->getMock(PlaceholderInterface::class);
        $placeholder1->expects($this->once())->method('replaceDefault')
            ->with('string_PLACEHOLDER1_PLACEHOLDER2')
            ->willReturn('string_value1_PLACEHOLDER2');

        $placeholder2 = $this->getMock(PlaceholderInterface::class);
        $placeholder2->expects($this->once())->method('replaceDefault')
            ->with('string_value1_PLACEHOLDER2')
            ->willReturn('string_value1_value2');

        $this->registry->expects($this->once())->method('getPlaceholders')->willReturn([$placeholder1, $placeholder2]);

        $this->assertEquals(
            'string_value1_value2',
            $this->placeholder->replaceDefault('string_PLACEHOLDER1_PLACEHOLDER2')
        );
    }
}
