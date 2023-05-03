<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType;
use Oro\Bundle\CatalogBundle\Tests\Unit\ContentVariantType\Stub\ContentVariantStub;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CategoryPageContentVariantTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var CategoryPageContentVariantType */
    private $type;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->type = new CategoryPageContentVariantType(
            $this->authorizationChecker,
            $this->getPropertyAccessor()
        );
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
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_catalog_category_view')
            ->willReturn(true);
        $this->assertTrue($this->type->isAllowed());
    }

    /**
     * @dataProvider getRouteDataProvider
     */
    public function testGetRouteData(
        ContentVariantStub $contentVariant,
        bool $expectedIncludeSubcategories,
        bool $expectedOverrideVariantConfiguration
    ) {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 42]);
        $contentVariant->setCategoryPageCategory($category);

        $this->assertEquals(
            new RouteData(
                'oro_product_frontend_product_index',
                [
                    'categoryContentVariantId' => 1,
                    'categoryId' => 42,
                    'includeSubcategories' => $expectedIncludeSubcategories,
                    'overrideVariantConfiguration' => $expectedOverrideVariantConfiguration
                ]
            ),
            $this->type->getRouteData($contentVariant)
        );
    }

    public function testGetAttachedEntity()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 42]);
        $contentVariant = new ContentVariantStub();
        $contentVariant->setCategoryPageCategory($category);

        $this->assertEquals(
            $category,
            $this->type->getAttachedEntity($contentVariant)
        );
    }

    public function getRouteDataProvider(): array
    {
        return [
            'include subcategories' => [
                'contentVariant' => (new ContentVariantStub())
                    ->setExcludeSubcategories(false)
                    ->setOverrideVariantConfiguration(false),
                'expectedIncludeSubcategories' => true,
                'overrideVariantConfiguration' => false
            ],
            'exclude subcategories' => [
                'contentVariant' => (new ContentVariantStub())
                    ->setExcludeSubcategories(true)
                    ->setOverrideVariantConfiguration(false),
                'expectedIncludeSubcategories' => false,
                'overrideVariantConfiguration' => false
            ],
            'override variant configuration' => [
                'contentVariant' => (new ContentVariantStub())
                    ->setExcludeSubcategories(true)
                    ->setOverrideVariantConfiguration(true),
                'expectedIncludeSubcategories' => false,
                'overrideVariantConfiguration' => true
            ]
        ];
    }

    public function testGetApiResourceClassName()
    {
        $this->assertEquals(Category::class, $this->type->getApiResourceClassName());
    }

    public function testGetApiResourceIdentifierDqlExpression()
    {
        $this->assertEquals(
            'IDENTITY(e.category_page_category)',
            $this->type->getApiResourceIdentifierDqlExpression('e')
        );
    }
}
