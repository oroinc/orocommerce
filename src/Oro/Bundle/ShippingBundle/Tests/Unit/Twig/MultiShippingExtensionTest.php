<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Oro\Bundle\ShippingBundle\Twig\MultiShippingExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MultiShippingExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private MultiShippingIntegrationManager|MockObject $multiShippingIntegrationManager;
    private MultiShippingExtension $extension;

    protected function setUp(): void
    {
        $this->multiShippingIntegrationManager = $this->createMock(MultiShippingIntegrationManager::class);
        $this->extension = new MultiShippingExtension($this->multiShippingIntegrationManager);
    }

    /**
     * @param bool $integrationExists
     * @param bool $expected
     * @dataProvider getDataForTestIsMultiShippingIntegrationExists
     */
    public function testIsMultiShippingIntegrationExists(bool $integrationExists, bool $expected)
    {
        $this->multiShippingIntegrationManager->expects($this->once())
            ->method('integrationExists')
            ->willReturn($integrationExists);

        $this->assertEquals(
            $expected,
            $this->callTwigFunction($this->extension, 'multi_shipping_integration_exists', [])
        );
    }

    public function getDataForTestIsMultiShippingIntegrationExists(): array
    {
        return [
            [
                'integrationExists' => false,
                'expected' => false
            ],
            [
                'integrationExists' => true,
                'expected' => true
            ]
        ];
    }
}
