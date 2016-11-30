<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryPageContentVariantTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var CategoryPageContentVariantType
     */
    private $type;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new CategoryPageContentVariantType($this->securityFacade, $this->getPropertyAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('category_page', $this->type->getName());
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.catalog.category.entity_label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(CategoryPageVariantType::class, $this->type->getFormType());
    }

    public function testIsAllowed()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);
        $this->assertTrue($this->type->isAllowed());
    }

    public function testGetRouteData()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();

        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 42]);
        $contentVariant->setCategoryPageCategory($category);

        $this->assertEquals(
            new RouteData('oro_product_frontend_product_index', ['categoryId' => 42, 'includeSubcategories' => true]),
            $this->type->getRouteData($contentVariant)
        );
    }
}
