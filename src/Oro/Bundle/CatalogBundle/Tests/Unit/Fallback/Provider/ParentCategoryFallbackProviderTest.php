<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackArgumentException;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;

class ParentCategoryFallbackProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ParentCategoryFallbackProvider
     */
    protected $parentCategoryFallbackProvider;

    protected function setUp()
    {
        $this->parentCategoryFallbackProvider = new ParentCategoryFallbackProvider();
    }

    public function testIsFallbackSupportedReturnsFalseIfNotCategory()
    {
        $this->assertFalse($this->parentCategoryFallbackProvider->isFallbackSupported(new \stdClass(), 'test'));
    }

    public function testIsFallbackSupportedReturnsFalseIfNoParentCategory()
    {
        $object = new Category();
        $object->setParentCategory(null);
        $this->assertFalse($this->parentCategoryFallbackProvider->isFallbackSupported($object, 'test'));
    }

    public function testIsFallbackSupportedReturnsTrueIfCaregoryAndHasParent()
    {
        $object = new Category();
        $object->setParentCategory(new Category());
        $this->assertTrue($this->parentCategoryFallbackProvider->isFallbackSupported($object, 'test'));
    }

    public function testGetFallbackHolderEntityThrowsException()
    {
        $this->setExpectedException(InvalidFallbackArgumentException::class);
        $this->parentCategoryFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
    }

    public function testGetFallbackHolderEntityReturnsParent()
    {
        $object = new Category();
        $parent = new Category();
        $object->setParentCategory($parent);
        $result = $this->parentCategoryFallbackProvider->getFallbackHolderEntity($object, 'test');
        $this->assertSame($parent, $result);
    }
}
