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
    const CLASS_NAME = Item::class;
    const FIELD_NAME = 'test_field_name';

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

    /**
     * @param string $className
     */
    protected function assertClassName($className)
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
     *
     * @param string $method
     */
    public function testAttributeConfigurationInterfaceMethods($method)
    {
        $result = 'test_value';

        $this->attributeType->expects($this->once())
            ->method($method)
            ->with($this->attribute)
            ->willReturn($result);

        $this->assertSame($result, $this->getSearchableAttributeType()->$method($this->attribute));
    }

    /**
     * @return array
     */
    public function configurationMethodsProvider()
    {
        return [
            ['isSearchable'],
            ['isFilterable'],
            ['isSortable'],
        ];
    }

    /**
     * @dataProvider valueMethodsProvider
     *
     * @param string $method
     */
    public function testAttributeValueInterfaceMethods($method)
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

    /**
     * @return array
     */
    public function valueMethodsProvider()
    {
        return [
            ['getSearchableValue'],
            ['getFilterableValue'],
            ['getSortableValue'],
        ];
    }
}
