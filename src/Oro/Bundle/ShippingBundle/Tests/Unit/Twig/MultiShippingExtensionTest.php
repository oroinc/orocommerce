<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Twig;

use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Oro\Bundle\ShippingBundle\Twig\MultiShippingExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class MultiShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var MultiShippingIntegrationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $multiShippingIntegrationManager;

    /** @var MultiShippingExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->multiShippingIntegrationManager = $this->createMock(MultiShippingIntegrationManager::class);

        $container = self::getContainerBuilder()
            ->add('oro_shipping.manager.multi_shipping_integration', $this->multiShippingIntegrationManager)
            ->getContainer($this);

        $this->extension = new MultiShippingExtension($container);
    }

    /**
     * @dataProvider getDataForTestIsMultiShippingIntegrationExists
     */
    public function testIsMultiShippingIntegrationExists(bool $integrationExists, bool $expected)
    {
        $this->multiShippingIntegrationManager->expects($this->once())
            ->method('integrationExists')
            ->willReturn($integrationExists);

        $this->assertSame(
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
