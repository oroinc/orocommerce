<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\MultiEnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

class MultiEnumSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return MultiEnumSearchableAttributeType::class;
    }

    public function testGetFilterStorageFieldTypes()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => Query::TYPE_INTEGER],
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
            SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
            $this->getSearchableAttributeType()->getFilterType()
        );
    }

    public function testIsLocalizable()
    {
        $this->assertFalse($this->getSearchableAttributeType()->isLocalizable($this->attribute));
    }

    public function testGetFilterableFieldNames()
    {
        $this->assertSame(
            [SearchAttributeTypeInterface::VALUE_MAIN => self::FIELD_NAME . '_' . EnumIdPlaceholder::NAME],
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
