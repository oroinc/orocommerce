<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\OneToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class OneToManySearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return OneToManySearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldTypes()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => Query::TYPE_TEXT],
            $this->getSearchableAttributeType()->getFilterStorageFieldTypes()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSorterStorageFieldType()
    {
        $this->getSearchableAttributeType()->getSorterStorageFieldType();
    }

    public function testGetFilterType()
    {
        $this->assertSame(
            SearchAttributeTypeInterface::FILTER_TYPE_STRING,
            $this->getSearchableAttributeType()->getFilterType()
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSortableFieldNameException()
    {
        $this->getSearchableAttributeType()->getSortableFieldName($this->attribute);
    }
}
