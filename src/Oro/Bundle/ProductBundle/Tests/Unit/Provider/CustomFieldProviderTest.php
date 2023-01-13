<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Symfony\Contracts\Cache\CacheInterface;

class CustomFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = \stdClass::class;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var CustomFieldProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);

        $this->provider = new CustomFieldProvider($this->extendConfigProvider, $this->entityConfigProvider);
    }

    public function testGetEntityCustomFieldsWithCache(): void
    {
        $data = [
            'size' => [
                'name' => 'size',
                'label' => 'Size Label',
                'type' => 'string',
                'is_serialized' => true
            ],
            'color' => [
                'name' => 'color',
                'label' => 'Color Label',
                'type' => 'string',
                'is_serialized' => false,
            ],
        ];

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with(UniversalCacheKeyGenerator::normalizeCacheKey(self::CLASS_NAME))
            ->willReturn($data);

        $this->provider->setCache($cache);

        $this->extendConfigProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($data, $this->provider->getEntityCustomFields(self::CLASS_NAME));
    }

    public function getEntityCustomFieldsDataProvider(): array
    {
        return [
            'all_fields' => [
                'fields' => [
                    'size' => [
                        'owner' => 'Custom',
                        'label' => 'Size Label',
                        'type' => 'string',
                        'state' => 'Active',
                        'is_serialized' => true,
                    ],
                    'color' => [
                        'owner' => 'Custom',
                        'label' => 'Color Label',
                        'type' => 'string',
                        'state' => 'Requires update',
                        'is_serialized' => false,
                    ],
                    'weight' => [
                        'owner' => 'Custom',
                        'label' => 'Weight Label',
                        'type' => 'string',
                        'state' => 'New',
                    ],
                    'id' => [
                        'owner' => 'System',
                        'label' => 'Id Label',
                        'type' => 'string',
                    ],
                ],

                'expectedResult' => [
                    'size' => [
                        'name' => 'size',
                        'label' => 'Size Label',
                        'type' => 'string',
                        'is_serialized' => true
                    ],
                    'color' => [
                        'name' => 'color',
                        'label' => 'Color Label',
                        'type' => 'string',
                        'is_serialized' => false,
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider getEntityCustomFieldsDataProvider
     */
    public function testGetEntityCustomFields(array $fields, array $expectedResult)
    {
        $extendsConfigs = [];
        foreach ($fields as $fieldName => $fieldData) {
            $extendsConfigs[$fieldName] = $this->createConfigByScope('extend', $fieldName, $fieldData);
        }

        $entityConfigs = [];
        foreach ($fields as $fieldName => $fieldData) {
            $entityConfigs[$fieldName] =  $this->createConfigByScope('entity', $fieldName, $fieldData);
        }

        $this->extendConfigProvider->expects(self::once())
            ->method('getConfigs')
            ->with(self::CLASS_NAME)
            ->willReturn($extendsConfigs);

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigById')
            ->willReturnCallback(function (FieldConfigId $configId) use ($entityConfigs) {
                return $entityConfigs[$configId->getFieldName()];
            });

        $this->assertEquals($expectedResult, $this->provider->getEntityCustomFields(self::CLASS_NAME));

        //check array cache
        $this->assertEquals($expectedResult, $this->provider->getEntityCustomFields(self::CLASS_NAME));
    }

    public function getVariantFieldsDataProvider(): array
    {
        return [
            'all_fields' => [
                'fields' => [
                    'size' => [
                        'owner' => 'Custom',
                        'label' => 'Size Label',
                        'type' => 'boolean',
                        'state' => 'Active',
                        'is_serialized' => false,
                    ],
                    'color' => [
                        'owner' => 'Custom',
                        'label' => 'Color Label',
                        'type' => 'enum',
                        'state' => 'Requires update',
                        'is_serialized' => false,
                    ],
                    'weight' => [
                        'owner' => 'Custom',
                        'label' => 'Weight Label',
                        'type' => 'string',
                        'state' => 'New',
                        'is_serialized' => true
                    ],
                    'id' => [
                        'owner' => 'System',
                        'label' => 'Id Label',
                        'type' => 'string',
                    ],
                ],

                'expectedResult' => [
                    'size' => [
                        'name' => 'size',
                        'label' => 'Size Label',
                        'type' => 'boolean',
                        'is_serialized' => false
                    ],
                    'color' => [
                        'name' => 'color',
                        'label' => 'Color Label',
                        'type' => 'enum',
                        'is_serialized' => false,
                    ],
                ],
            ]
        ];
    }

    private function createConfig(string $scope, string $fieldName, string $fieldType, array $values = []): Config
    {
        return new Config(new FieldConfigId($scope, self::CLASS_NAME, $fieldName, $fieldType), $values);
    }

    private function createConfigByScope(string $scope, string $fieldName, array $fieldData): Config
    {
        return $this->createConfig($scope, $fieldName, $fieldData['type'], $fieldData);
    }
}
