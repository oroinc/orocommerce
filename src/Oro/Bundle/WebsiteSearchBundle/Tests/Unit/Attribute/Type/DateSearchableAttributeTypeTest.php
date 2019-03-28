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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetFilterStorageFieldTypes()
    {
        $this->getSearchableAttributeType()->getFilterStorageFieldTypes();
    }

    public function testGetSorterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_DATETIME,
            $this->getSearchableAttributeType()->getSorterStorageFieldType()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetFilterTypeException()
    {
        $this->getSearchableAttributeType()->getFilterType();
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetFilterableFieldNameException()
    {
        $this->getSearchableAttributeType()->getFilterableFieldNames($this->attribute);
    }

    public function testGetSortableFieldName()
    {
        $this->assertSame(
            self::FIELD_NAME,
            $this->getSearchableAttributeType()->getSortableFieldName($this->attribute)
        );
    }
}
