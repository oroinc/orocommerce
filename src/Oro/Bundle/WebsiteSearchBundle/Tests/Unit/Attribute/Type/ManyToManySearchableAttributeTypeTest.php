<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class ManyToManySearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return ManyToManySearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldTypes()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => Query::TYPE_TEXT],
            $this->getSearchableAttributeType()->getFilterStorageFieldTypes($this->attribute)
        );
    }

    public function testGetSorterStorageFieldType()
    {
        $this->assertSame(
            Query::TYPE_TEXT,
            $this->getSearchableAttributeType()->getSorterStorageFieldType($this->attribute)
        );
    }

    public function testGetFilterType()
    {
        $this->assertSame(
            SearchAttributeTypeInterface::FILTER_TYPE_STRING,
            $this->getSearchableAttributeType()->getFilterType($this->attribute)
        );
    }

    public function testIsLocalizable()
    {
        $this->assertTrue($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldNames()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => self::FIELD_NAME . '_' . LocalizationIdPlaceholder::NAME],
            $this->getSearchableAttributeType()->getFilterableFieldNames($this->attribute)
        );
    }

    public function testGetSortableFieldName()
    {
        $this->assertSame(
            self::FIELD_NAME . '_' . LocalizationIdPlaceholder::NAME,
            $this->getSearchableAttributeType()->getSortableFieldName($this->attribute)
        );
    }

    public function testGetSearchableFieldName()
    {
        $this->assertSame(
            self::FIELD_NAME . '_' . LocalizationIdPlaceholder::NAME,
            $this->getSearchableAttributeType()->getSearchableFieldName($this->attribute)
        );
    }
}
