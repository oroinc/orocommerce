<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider;

class CustomFieldProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomFieldProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Config
     */
    protected $extendConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var string
     */
    protected $className = '\stdClass';

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->extendConfigProvider = $this->getMockShortcut('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider');
        $this->extendConfig = $this->getMockShortcut('Oro\Bundle\EntityConfigBundle\Config\Config');
        $this->entityFieldProvider = $this->getMockShortcut('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider');

        $this->provider = new CustomFieldProvider($this->extendConfigProvider, $this->entityFieldProvider);
    }

    public function testGetEntityCustomFields()
    {
        $allFields = [
            ['name' => 'size', 'label' => 'Size Label', 'type' => 'string'],
            ['name' => 'color', 'label' => 'Color Label', 'type' => 'string'],
            ['name' => 'id', 'label' => 'Id Label', 'type' => 'integer'],
        ];

        $customFieldsFromConfig = [
            'size' => 'size',
            'color' => 'color',
            'serialized_data' => 'serialized_data'
        ];

        $expectedResult = [
            'size' => ['name' => 'size', 'label' => 'Size Label', 'type' => 'string'],
            'color' => ['name' => 'color', 'label' => 'Color Label', 'type' => 'string']
        ];

        $this->extendConfig
            ->expects($this->once())
            ->method('get')
            ->with('schema')
            ->willReturn(['property' => $customFieldsFromConfig]);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($this->className)
            ->willReturn($this->extendConfig);

        $this->entityFieldProvider
            ->expects($this->once())
            ->method('getFields')
            ->with($this->className)
            ->willReturn($allFields);

        $this->assertEquals($expectedResult, $this->provider->getEntityCustomFields($this->className));
    }

    /**
     * @param $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockShortcut($className)
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
