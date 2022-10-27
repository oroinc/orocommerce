<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\DateSearchableAttributeType;

class DateSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return DateSearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldTypes()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getSearchableAttributeType()->getFilterStorageFieldTypes($this->attribute);
    }

    public function testGetSorterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_DATETIME,
            $this->getSearchableAttributeType()->getSorterStorageFieldType($this->attribute)
        );
    }

    public function testGetFilterTypeException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getSearchableAttributeType()->getFilterType($this->attribute);
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldNameException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not supported');

        $this->getSearchableAttributeType()->getFilterableFieldNames($this->attribute);
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
