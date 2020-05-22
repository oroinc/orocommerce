<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider;

class SystemDefaultProductUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var SystemDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultProductUnitProvider = new SystemDefaultProductUnitProvider(
            $this->configManager,
            $this->doctrineHelper
        );
    }

    public function testGetDefaultProductUnitPrecision()
    {
        $unitCode = 'each';
        $precision = 10;

        $this->configManager->expects(static::at(0))
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn($unitCode);

        $this->configManager->expects(static::at(1))
            ->method('get')
            ->with('oro_product.default_unit_precision')
            ->willReturn($precision);

        $unit = $this->createMock(ProductUnit::class);
        $this->doctrineHelper->expects(static::once())
            ->method('getEntityReference')
            ->with(ProductUnit::class, $unitCode)
            ->willReturn($unit);

        $expectedUnitPrecision = new ProductUnitPrecision();
        $expectedUnitPrecision->setUnit($unit)->setPrecision($precision);

        $this->assertEquals(
            $expectedUnitPrecision,
            $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()
        );
    }
}
