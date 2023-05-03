<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderRegistry;

class PlaceholderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var PlaceholderRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new PlaceholderRegistry();
    }

    private function getPlaceholder(string $placeholderName): PlaceholderInterface
    {
        $placeholder = $this->createMock(PlaceholderInterface::class);
        $placeholder->expects($this->any())
            ->method('getPlaceholder')
            ->willReturn($placeholderName);

        return $placeholder;
    }

    public function testAddPlaceholder()
    {
        $placeholder = $this->getPlaceholder('TEST_PLACEHOLDER');

        $this->registry->addPlaceholder($placeholder);

        $this->assertEquals(['TEST_PLACEHOLDER' => $placeholder], $this->registry->getPlaceholders());
    }

    public function testAddPlaceholderWithReplace()
    {
        $placeholder = $this->getPlaceholder('TEST_PLACEHOLDER');
        $placeholder2 = $this->getPlaceholder('TEST_PLACEHOLDER');

        $this->registry->addPlaceholder($placeholder);
        $this->registry->addPlaceholder($placeholder2);

        $this->assertEquals(['TEST_PLACEHOLDER' => $placeholder2], $this->registry->getPlaceholders());
    }

    public function testGetPlaceholder()
    {
        $placeholder = $this->getPlaceholder('TEST_PLACEHOLDER');

        $this->registry->addPlaceholder($placeholder);

        $retrievedPlaceholder = $this->registry->getPlaceholder($placeholder->getPlaceholder());
        $this->assertSame($placeholder, $retrievedPlaceholder);
    }

    public function testGetPlaceholderUnknownName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Placeholder "UNKNOWN_PLACEHOLDER" does not exist.');

        $placeholder = $this->getPlaceholder('TEST_PLACEHOLDER');
        $this->registry->addPlaceholder($placeholder);

        $this->registry->getPlaceholder('UNKNOWN_PLACEHOLDER');
    }

    public function testGetPlaceholders()
    {
        $placeholder = $this->getPlaceholder('TEST_PLACEHOLDER');
        $placeholder2 = $this->getPlaceholder('TEST_PLACEHOLDER2');

        $this->registry->addPlaceholder($placeholder);
        $this->registry->addPlaceholder($placeholder2);

        $this->assertIsArray($this->registry->getPlaceholders());
        $this->assertEquals(
            [
                'TEST_PLACEHOLDER' => $placeholder,
                'TEST_PLACEHOLDER2' => $placeholder2
            ],
            $this->registry->getPlaceholders()
        );
    }
}
