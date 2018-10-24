<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\BooleanSearchableAttributeType;

class BooleanSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return BooleanSearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_INTEGER,
            $this->getSearchableAttributeType()->getFilterStorageFieldType()
        );
    }

    public function testGetSorterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_INTEGER,
            $this->getSearchableAttributeType()->getSorterStorageFieldType()
        );
    }

    public function testGetFilterTypeException()
    {
        $this->assertSame(
            'boolean',
            $this->getSearchableAttributeType()->getFilterType()
        );
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldNameException()
    {
        $this->assertSame(
            self::FIELD_NAME,
            $this->getSearchableAttributeType()->getFilterableFieldName($this->attribute)
        );
    }

    public function testGetSortableFieldName()
    {
        $this->assertSame(
            self::FIELD_NAME,
            $this->getSearchableAttributeType()->getSortableFieldName($this->attribute)
        );
    }
}
