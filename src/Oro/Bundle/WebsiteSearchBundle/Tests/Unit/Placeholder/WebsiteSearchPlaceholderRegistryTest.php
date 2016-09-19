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
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER');

        $this->registry->addPlaceholder($placeholder);

        $this->assertEquals(['TEST_PLACEHOLDER' => $placeholder], $this->registry->getPlaceholders());
    }

    public function testAddPlaceholderWithReplace()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER');
        $placeholder2 = $this->preparePlaceholder('TEST_PLACEHOLDER');

        $this->registry->addPlaceholder($placeholder);
        $this->registry->addPlaceholder($placeholder2);

        $this->assertEquals(['TEST_PLACEHOLDER' => $placeholder2], $this->registry->getPlaceholders());
    }

    public function testGetPlaceholder()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER');

        $this->registry->addPlaceholder($placeholder);

        $retrievedPlaceholder = $this->registry->getPlaceholder($placeholder->getPlaceholder());
        $this->assertSame($placeholder, $retrievedPlaceholder);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Placeholder "UNKNOWN_PLACEHOLDER" does not exist.
     */
    public function testGetPlaceholderUnknownName()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER');
        $this->registry->addPlaceholder($placeholder);

        $this->registry->getPlaceholder('UNKNOWN_PLACEHOLDER');
    }

    public function testGetPlaceholders()
    {
        $placeholder = $this->preparePlaceholder('TEST_PLACEHOLDER');
        $placeholder2 = $this->preparePlaceholder('TEST_PLACEHOLDER2');

        $this->registry->addPlaceholder($placeholder);
        $this->registry->addPlaceholder($placeholder2);

        $this->assertInternalType('array', $this->registry->getPlaceholders());
        $this->assertEquals(
            [
                'TEST_PLACEHOLDER' => $placeholder,
                'TEST_PLACEHOLDER2' => $placeholder2
            ],
            $this->registry->getPlaceholders()
        );
    }

    /**
     * @param string $placeholderName
     * @return WebsiteSearchPlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function preparePlaceholder($placeholderName)
    {
        $placeholder = $this->getMock(WebsiteSearchPlaceholderInterface::class);

        $placeholder->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn($placeholderName);

        return $placeholder;
    }
}
