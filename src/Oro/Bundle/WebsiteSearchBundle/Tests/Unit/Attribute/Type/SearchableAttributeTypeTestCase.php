<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;

abstract class SearchableAttributeTypeTestCase extends \PHPUnit\Framework\TestCase
{
    protected const CLASS_NAME = Item::class;
    protected const FIELD_NAME = 'test_field_name';

    /** @var FieldConfigModel */
    protected $attribute;

    /** @var Localization */
    protected $localization;

    /** @var AttributeTypeInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeType;

    protected function setUp(): void
    {
        $entity = new EntityConfigModel(self::CLASS_NAME);

        $this->attribute = new FieldConfigModel(self::FIELD_NAME);
        $this->attribute->setEntity($entity);

        $this->localization = new Localization();

        $this->attributeType = $this->createMock(AttributeTypeInterface::class);
    }

    /**
     * @return string
     */
    abstract protected function getSearchableAttributeTypeClassName();

    /**
     * @return SearchAttributeTypeInterface
     */
    protected function getSearchableAttributeType()
    {
        $className = $this->getSearchableAttributeTypeClassName();

        $this->assertClassName($className);

        return new $className($this->attributeType);
    }

    protected function assertClassName(string $className): void
    {
        $this->assertTrue(
            is_a(
                $className,
                SearchAttributeTypeInterface::class,
                true
            ),
            sprintf(
                'Class "%s" should extend the "%s" interface',
                $className,
                SearchAttributeTypeInterface::class
            )
        );
    }

    /**
     * @dataProvider configurationMethodsProvider
     */
    public function testAttributeConfigurationInterfaceMethods(string $method)
    {
        $result = 'test_value';

        $this->attributeType->expects($this->once())
            ->method($method)
            ->with($this->attribute)
            ->willReturn($result);

        $this->assertSame($result, $this->getSearchableAttributeType()->$method($this->attribute));
    }

    public function configurationMethodsProvider(): array
    {
        return [
            ['isSearchable'],
            ['isFilterable'],
            ['isSortable'],
        ];
    }

    /**
     * @dataProvider valueMethodsProvider
     */
    public function testAttributeValueInterfaceMethods(string $method)
    {
        $result = 'test_value';
        $originalValue = new \stdClass();

        $this->attributeType->expects($this->once())
            ->method($method)
            ->with($this->attribute, $originalValue, $this->localization)
            ->willReturn($result);

        $this->assertSame(
            $result,
            $this->getSearchableAttributeType()->$method($this->attribute, $originalValue, $this->localization)
        );
    }

    public function valueMethodsProvider(): array
    {
        return [
            ['getSearchableValue'],
            ['getFilterableValue'],
            ['getSortableValue'],
        ];
    }
}
