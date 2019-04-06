<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\FileSearchableAttributeType;

class FileSearchableAttributeTypeTest extends SearchableAttributeTypeTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getSearchableAttributeTypeClassName()
    {
        return FileSearchableAttributeType::class;
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetFilterStorageFieldTypes()
    {
        $this->getSearchableAttributeType()->getFilterStorageFieldTypes();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSorterStorageFieldType()
    {
        $this->getSearchableAttributeType()->getSorterStorageFieldType();
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not supported
     */
    public function testGetSortableFieldNameException()
    {
        $this->getSearchableAttributeType()->getSortableFieldName($this->attribute);
    }
}
