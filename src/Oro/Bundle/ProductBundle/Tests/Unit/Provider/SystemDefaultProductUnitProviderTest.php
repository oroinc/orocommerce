<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider;

class SystemDefaultProductUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var SystemDefaultProductUnitProvider */
    private $defaultProductUnitProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->defaultProductUnitProvider = new SystemDefaultProductUnitProvider(
            $this->configManager,
            $this->doctrineHelper
        );
    }

    public function testGetDefaultProductUnitPrecision()
    {
        $unitCode = 'each';
        $precision = 10;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_product.default_unit'],
                ['oro_product.default_unit_precision']
            )
            ->willReturnOnConsecutiveCalls(
                $unitCode,
                $precision
            );

        $unit = $this->createMock(ProductUnit::class);
        $this->doctrineHelper->expects(self::once())
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
