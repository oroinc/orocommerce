<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\DecimalSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;

class DecimalSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return DecimalSearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldTypes()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => Query::TYPE_DECIMAL],
            $this->getSearchableAttributeType()->getFilterStorageFieldTypes($this->attribute)
        );
    }

    public function testGetSorterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_DECIMAL,
            $this->getSearchableAttributeType()->getSorterStorageFieldType($this->attribute)
        );
    }

    public function testGetFilterType()
    {
        $this->assertSame(
            SearchAttributeTypeInterface::FILTER_TYPE_NUMBER_RANGE,
            $this->getSearchableAttributeType()->getFilterType($this->attribute)
        );
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldNames()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => self::FIELD_NAME],
            $this->getSearchableAttributeType()->getFilterableFieldNames($this->attribute)
        );
    }

    public function testGetSortableFieldName()
    {
        $this->assertSame(
            self::FIELD_NAME,
            $this->getSearchableAttributeType()->getSortableFieldName($this->attribute)
        );
    }

    public function testGetSearchableFieldName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getSearchableAttributeType()->getSearchableFieldName($this->attribute);
    }
}
