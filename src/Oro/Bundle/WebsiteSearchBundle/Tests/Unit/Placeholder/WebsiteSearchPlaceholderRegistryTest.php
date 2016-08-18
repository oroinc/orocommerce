<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry;

class WebsiteSearchPlaceholderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebsiteSearchPlaceholderRegistry */
    private $registry;

    protected function setUp()
    {
        $this->registry = new WebsiteSearchPlaceholderRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testAddPlaceholder()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER', 'test');

        $this->registry->addPlaceholder($placeholder);

        $this->assertEquals(['TEST_PLACEHOLDER' => 'test'], $this->registry->getPlaceholders());
    }

    public function testAddPlaceholderWithReplace()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER', 'test');
        $placeholder2 = $this->preparePlaceholder('TEST_PLACEHOLDER', 'test2_value');

        $this->registry->addPlaceholder($placeholder);
        $this->registry->addPlaceholder($placeholder2);

        $this->assertEquals(['TEST_PLACEHOLDER' => 'test2_value'], $this->registry->getPlaceholders());
    }

    public function testGetPlaceholderValueSuccess()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER', 'test');
        $this->registry->addPlaceholder($placeholder);

        $this->assertEquals('test', $this->registry->getPlaceholderValue('TEST_PLACEHOLDER'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Placeholder "TEST_ERROR_PLACEHOLDER" does not exist.
     */
    public function testGetPlaceholderValueWithException()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER', 'test');
        $this->registry->addPlaceholder($placeholder);

        $this->registry->getPlaceholderValue('TEST_ERROR_PLACEHOLDER');
    }

    public function testGetPlaceholders()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER', 'test');
        $placeholder2 = $this->preparePlaceholder('TEST_PLACEHOLDER2', 'test2_value');

        $this->registry->addPlaceholder($placeholder);
        $this->registry->addPlaceholder($placeholder2);

        $this->assertInternalType('array', $this->registry->getPlaceholders());
        $this->assertEquals(
            [
                'TEST_PLACEHOLDER' => 'test',
                'TEST_PLACEHOLDER2' => 'test2_value'
            ],
            $this->registry->getPlaceholders()
        );
    }

    /**
     * @param string $placeholderName
     * @param string $value
     * @return WebsiteSearchPlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function preparePlaceholder($placeholderName, $value)
    {
        $placeholder = $this
            ->getMockBuilder('Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderInterface')
            ->getMock();

        $placeholder->expects($this->once())
            ->method('getPlaceholder')
            ->willReturn($placeholderName);

        $placeholder->expects($this->once())
            ->method('getValue')
            ->willReturn($value);

        return $placeholder;
    }
}
