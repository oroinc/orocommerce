<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\StringSearchableAttributeType;

class StringSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return StringSearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_TEXT,
            $this->getSearchableAttributeType()->getFilterStorageFieldType()
        );
    }

    public function testGetSorterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_TEXT,
            $this->getSearchableAttributeType()->getSorterStorageFieldType()
        );
    }

    public function testGetFilterType()
    {
        $this->assertSame(
            SearchableAttributeTypeInterface::FILTER_TYPE_STRING,
            $this->getSearchableAttributeType()->getFilterType()
        );
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldName()
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
