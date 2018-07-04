<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\EventListener\WebsiteSearchEntityConfigListener;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class WebsiteSearchEntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $mappingProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var WebsiteSearchEntityConfigListener */
    protected $listener;

    protected function setUp()
    {
        $this->mappingProvider = $this->createMock(WebsiteSearchMappingProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new WebsiteSearchEntityConfigListener($this->mappingProvider);
    }

    public function testPostFlushUnsupportedModel()
    {
        $this->mappingProvider->expects($this->never())->method($this->anything());

        $this->listener->postFlush(
            new PostFlushConfigEvent([new \stdClass()], $this->configManager)
        );
    }

    public function testPostFlushUnsupportedModelEntityClass()
    {
        $this->mappingProvider->expects($this->never())->method($this->anything());

        $this->listener->postFlush(
            new PostFlushConfigEvent([$this->getFieldConfigModel(\stdClass::class, 'field1')], $this->configManager)
        );
    }

    /**
     * @dataProvider postFlushDataProvider
     *
     * @param bool $isAttribute
     * @param int $expected
     */
    public function testPostFlush($isAttribute, $expected)
    {
        $fieldName = 'field1';

        /** @var ConfigIdInterface|\PHPUnit\Framework\MockObject\MockObject $configId */
        $configId = $this->createMock(ConfigIdInterface::class);
        $config = new Config($configId, ['is_attribute' => true]);

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $attributeConfigProvider */
        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects($this->once())
            ->method('getConfig')
            ->with(Product::class, $fieldName)
            ->willReturn($config);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('attribute')
            ->willReturn($provider);

        $this->mappingProvider->expects($this->once())
            ->method('clearCache');

        $this->listener->postFlush(
            new PostFlushConfigEvent([$this->getFieldConfigModel(Product::class, $fieldName)], $this->configManager)
        );
    }

    /**
     * @return array
     */
    public function postFlushDataProvider()
    {
        return [
            'attribute' => [
                'isAttribute' => true,
                'expected' => 1,
            ],
            'not attribute' => [
                'isAttribute' => false,
                'expected' => 0,
            ]
        ];
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return FieldConfigModel
     */
    protected function getFieldConfigModel($className, $fieldName)
    {
        $entityModel = new EntityConfigModel();
        $entityModel->setClassName($className);

        $fieldModel = new FieldConfigModel();
        $fieldModel->setFieldName($fieldName)->setEntity($entityModel);

        return $fieldModel;
    }
}
