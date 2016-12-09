<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Provider\CustomVariantFieldsProvider;

class CustomVariantFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomVariantFieldsProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var string
     */
    protected $className = '\stdClass';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->extendConfigProvider = $this->getMockForClass(ConfigProvider::class);
        $this->entityConfigProvider = $this->getMockForClass(ConfigProvider::class);

        $this->provider = new CustomVariantFieldsProvider($this->extendConfigProvider, $this->entityConfigProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityCustomFieldsDataProvider()
    {
        return [
            'variant_fields_only' => [
                'fields' => [
                    'size_string' => [
                        'owner' => 'Custom',
                        'label' => 'Size Label',
                        'type' => 'string',
                        'state' => 'Active',
                    ],
                    'color_string' => [
                        'owner' => 'Custom',
                        'label' => 'Color Label',
                        'type' => 'string',
                        'state' => 'Requires update',
                    ],
                    'size_select' => [
                        'owner' => 'Custom',
                        'label' => 'Size Label',
                        'type' => 'enum',
                        'state' => 'Active',
                    ],
                    'color_select' => [
                        'owner' => 'Custom',
                        'label' => 'Color Label',
                        'type' => 'enum',
                        'state' => 'Requires update',
                    ],
                    'slim_fit' => [
                        'owner' => 'Custom',
                        'label' => 'Slim Fit Label',
                        'type' => 'boolean',
                        'state' => 'Active',
                    ],
                    'category' => [
                        'owner' => 'Custom',
                        'label' => 'Category Label',
                        'type' => 'manyToOne',
                        'state' => 'Active',
                    ],
                ],

                'expectedResult' => [
                    'size_select' => ['name' => 'size_select', 'label' => 'Size Label', 'type' => 'enum'],
                    'color_select' => ['name' => 'color_select', 'label' => 'Color Label', 'type' => 'enum'],
                    'slim_fit' => ['name' => 'slim_fit', 'label' => 'Slim Fit Label', 'type' => 'boolean'],
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
    }

    /**
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockForClass($className)
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
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
