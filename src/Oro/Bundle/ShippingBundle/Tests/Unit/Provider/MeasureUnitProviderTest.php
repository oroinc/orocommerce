<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitProvider;

class MeasureUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    protected const CONFIG_ENTRY_NAME = 'oro_shipping.weight_units';

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var MeasureUnitProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new MeasureUnitProvider(
            $this->repository,
            $this->configManager,
            self::CONFIG_ENTRY_NAME
        );
    }

    /**
     * @dataProvider unitsProvider
     */
    public function testGetUnits(
        mixed $ormData,
        mixed $configData,
        mixed $expected,
        bool $onlyEnabled = true
    ) {
        $this->repository->expects($this->atLeastOnce())
            ->method('findAll')
            ->willReturn($ormData);

        if ($onlyEnabled) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with(self::CONFIG_ENTRY_NAME, false)
                ->willReturn($configData);
        } else {
            $this->configManager->expects($this->never())
                ->method('get');
        }

        $units = $this->provider->getUnits($onlyEnabled);

        $this->assertEquals($units, $expected);

        if (count($units)) {
            foreach ($units as $unit) {
                $this->assertInstanceOf(WeightUnit::class, $unit);
            }
        }
    }

    public function unitsProvider(): array
    {
        $weightUnit1 = (new WeightUnit())->setCode('test 1');
        $weightUnit2 = (new WeightUnit())->setCode('test 2');
        $weightUnit3 = (new WeightUnit())->setCode('test 3');

        return [
            [
                'ormData' => [$weightUnit1, $weightUnit2, $weightUnit3],
                'configData' => ['test 1' => 'test 1'],
                'expected' => [$weightUnit1]
            ],
            [
                'ormData' => [$weightUnit1, $weightUnit2, $weightUnit3],
                'configData' => ['test 1' => 'test 1'],
                'expected' => [$weightUnit1, $weightUnit2, $weightUnit3],
                'onlyEnabled' => false
            ],
            [
                'ormData' => [],
                'configData' => ['test 1' => 'test 1'],
                'expected' => []
            ],
            [
                'ormData' => [],
                'configData' => ['test 1' => 'test 1'],
                'expected' => [],
                'onlyEnabled' => false
            ]
        ];
    }
}
