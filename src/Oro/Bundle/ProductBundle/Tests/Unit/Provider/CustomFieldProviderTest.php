<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class CustomFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CustomFieldProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var string
     */
    protected $className = '\stdClass';

    /**
     * {@inheritDoc}
     */
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

        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->once())
            ->method('fetch')
            ->with($this->className)
            ->willReturn($data);

        $this->provider->setCache($cache);

        $this->extendConfigProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($data, $this->provider->getEntityCustomFields($this->className));
    }

    /**
     * @return array
     */
    public function getEntityCustomFieldsDataProvider()
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
     * @param array $fields
     * @param array $expectedResult
     * @dataProvider getEntityCustomFieldsDataProvider
     */
    public function testGetEntityCustomFields($fields, $expectedResult)
    {
        $extendsConfigs = [];
        foreach ($fields as $fieldName => $fieldData) {
            $extendsConfigs[$fieldName] = $this->createConfigByScope('extend', $fieldName, $fieldData);
        }

        $entityConfigs = [];
        foreach ($fields as $fieldName => $fieldData) {
            $entityConfigs[$fieldName] =  $this->createConfigByScope('entity', $fieldName, $fieldData);
        }

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->with($this->className)
            ->willReturn($extendsConfigs);

        $this->entityConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->willReturnCallback(
                function (FieldConfigId $configId) use ($entityConfigs) {
                    return $entityConfigs[$configId->getFieldName()];
                }
            );

        $this->assertEquals($expectedResult, $this->provider->getEntityCustomFields($this->className));

        //check array cache
        $this->assertEquals($expectedResult, $this->provider->getEntityCustomFields($this->className));
    }

    /**
     * @return array
     */
    public function getVariantFieldsDataProvider()
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

    /**
     * @param string $scope
     * @param string $fieldName
     * @param string $fieldType
     * @param array $values
     * @return Config
     */
    private function createConfig($scope, $fieldName, $fieldType, array $values = [])
    {
        return new Config(new FieldConfigId($scope, $this->className, $fieldName, $fieldType), $values);
    }

    /**
     * @param string $fieldName
     * @param array $fieldData
     * @param string $scope
     * @return Config
     */
    private function createConfigByScope($scope, $fieldName, $fieldData)
    {
        return $this->createConfig($scope, $fieldName, $fieldData['type'], $fieldData);
    }
}
