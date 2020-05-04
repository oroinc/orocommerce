<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\VariantFieldValueHandler;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;
use Psr\Log\LoggerInterface;

class EnumVariantFieldValueHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $enumValueProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /** @var EnumVariantFieldValueHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->handler = new EnumVariantFieldValueHandler(
            $this->doctrineHelper,
            $this->enumValueProvider,
            $this->logger,
            $this->configManager
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset(
            $this->doctrineHelper,
            $this->enumValueProvider,
            $this->logger,
            $this->handler
        );
    }

    public function testGetType()
    {
        $this->assertEquals(EnumVariantFieldValueHandler::TYPE, $this->handler->getType());
    }

    public function testGetValues()
    {
        $fieldName = 'testField';
        $enumValues = ['red', 'green'];

        $fieldConfig = $this->createMock(FieldConfigModel::class);
        $fieldConfig->expects($this->once())
            ->method('toArray')
            ->with('extend')
            ->willReturn(['target_entity' => '\stdClass']);
        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, $fieldName)
            ->willReturn($fieldConfig);

        $this->enumValueProvider->expects($this->once())
            ->method('getEnumChoices')
            ->with('\stdClass')
            ->willReturn($enumValues);

        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));

        //check array cache
        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));
    }

    public function testGetValuesWithCache(): void
    {
        $fieldName = 'testField';
        $enumValues = ['red', 'green'];

        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->once())
            ->method('fetch')
            ->with($fieldName)
            ->willReturn($enumValues);

        $this->handler->setCache($cache);

        $this->configManager->expects($this->never())
            ->method('getConfigFieldModel');

        $this->enumValueProvider->expects($this->never())
            ->method('getEnumChoices');

        $this->assertEquals($enumValues, $this->handler->getPossibleValues($fieldName));
    }

    public function testGetScalarValue()
    {
        $fieldValue = new EnumValue();
        $scalarValue = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($fieldValue)
            ->willReturn($scalarValue);

        $this->assertEquals($scalarValue, $this->handler->getScalarValue($fieldValue));
    }
}
