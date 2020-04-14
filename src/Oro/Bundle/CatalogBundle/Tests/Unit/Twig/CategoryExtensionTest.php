<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\CatalogBundle\Twig\CategoryExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CategoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CategoryTreeHandler */
    protected $categoryTreeHandler;

    /** @var CategoryExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->categoryTreeHandler = $this->getMockBuilder(CategoryTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add(CategoryTreeHandler::class, $this->categoryTreeHandler)
            ->getContainer($this);

        $this->extension = new CategoryExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryExtension::NAME, $this->extension->getName());
    }

    public function testGetCategoryList()
    {
        $tree = [
            [
                'id' => 1,
                'parent' => '#',
                'text' => 'Master catalog',
                'state' => [
                    'opened' => true,
                ],
            ],
        ];

        $this->categoryTreeHandler->expects($this->once())
            ->method('createTree')
            ->with(null)
            ->willReturn($tree);

        $this->assertEquals(
            $tree,
            self::callTwigFunction($this->extension, 'oro_category_list', [])
        );
    }

    public function testGetCategoryListWithRootLabel()
    {
        $tree = [
            [
                'id' => 1,
                'parent' => '#',
                'text' => 'oro.catalog.frontend.category.master_category.label',
                'state' => [
                    'opened' => true,
                ],
            ],
        ];

        $this->categoryTreeHandler->expects($this->once())
            ->method('createTree')
            ->with(null)
            ->willReturn($tree);

        $this->assertEquals(
            $tree,
            self::callTwigFunction(
                $this->extension,
                'oro_category_list',
                ['oro.catalog.frontend.category.master_category.label']
            )
        );
    }

    public function testGetCategoryListWithRoot()
    {
        $tree = [
            [
                'id' => 1,
                'parent' => '#',
                'text' => 'oro.catalog.frontend.category.master_category.label',
                'state' => [
                    'opened' => true,
                ],
            ],
        ];

        $rootId = 1;
        $this->categoryTreeHandler->expects($this->once())
            ->method('createTree')
            ->with($rootId)
            ->willReturn($tree);

        $this->assertEquals(
            $tree,
            self::callTwigFunction(
                $this->extension,
                'oro_category_list',
                ['oro.catalog.frontend.category.master_category.label', $rootId]
            )
        );
    }

    public function testGetProductCategoryWithTwoCategories()
    {
        $category = new Category();
        $category->addTitle((new CategoryTitle())->setString('some string'));

        $parent = new Category();
        $parent->addTitle((new CategoryTitle())->setString('parent category title'));
        $category->setParentCategory($parent);

        $this->assertEquals(
            'parent category title / some string',
            $this->extension->getProductCategoryPath($category)
        );
        $this->assertEquals(
            'parent category title / some string',
            $this->extension->getProductCategoryTitle($category)
        );
    }

    public function testGetProductCategoryWithOneCategory()
    {
        $category = new Category();
        $category->addTitle((new CategoryTitle())->setString('some string'));

        $this->assertEquals('some string', $this->extension->getProductCategoryPath($category));
        $this->assertEquals('some string', $this->extension->getProductCategoryTitle($category));
    }

    public function testGetProductCategoryWithMoreThanTwoCategories()
    {
        $category = new Category();
        $category->addTitle((new CategoryTitle())->setString('some string'));

        $parent = new Category();
        $parent->addTitle((new CategoryTitle())->setString('parent category title'));
        $category->setParentCategory($parent);

        $rootCategory = new Category();
        $rootCategory->addTitle((new CategoryTitle())->setString('root category title'));
        $parent->setParentCategory($rootCategory);

        $this->assertEquals(
            'root category title /.../ some string',
            $this->extension->getProductCategoryTitle($category)
        );
        $this->assertEquals(
            'root category title / parent category title / some string',
            $this->extension->getProductCategoryPath($category)
        );
    }

    public function testGetProductCategoryWithCategoryWithoutTitle()
    {
        $category = new Category();

        $this->assertEquals('', $this->extension->getProductCategoryPath($category));
        $this->assertEquals('', $this->extension->getProductCategoryTitle($category));
    }
}
