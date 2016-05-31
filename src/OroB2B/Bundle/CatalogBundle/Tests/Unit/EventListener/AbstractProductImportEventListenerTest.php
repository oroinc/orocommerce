<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;

abstract class AbstractProductImportEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CATEGORY_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\Category';

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryRepository;

    /**
     * @var Category[]
     */
    protected $categoriesByProduct = [];

    /**
     * Count of repository `findOneByProductSku` calls by SKU
     *
     * @var int[]
     */
    protected $findByProductSkuCalls = [];

    /**
     * @var Category[]
     */
    protected $categoriesByTitle = [];

    /**
     * Count of repository `findOneByDefaultTitle` calls by title
     *
     * @var int[]
     */
    protected $findByDefaultTitleCalls = [];

    public function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->mockCategoryRepository();
    }

    public function tearDown()
    {
        unset($this->registry, $this->categoryRepository);
    }

    private function mockCategoryRepository()
    {
        $this->categoryRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepository
            ->expects($this->any())
            ->method('findOneByProductSku')
            ->willReturnCallback(
                function ($sku) {
                    if (!isset($this->categoriesByProduct[$sku])) {
                        return null;
                    }

                    $this->findByProductSkuCalls[$sku]++;

                    return $this->categoriesByProduct[$sku];
                }
            );

        $this->categoryRepository
            ->expects($this->any())
            ->method('findOneByDefaultTitle')
            ->willReturnCallback(
                function ($title) {
                    if (!isset($this->categoriesByTitle[$title])) {
                        return null;
                    }

                    $this->findByDefaultTitleCalls[$title]++;

                    return $this->categoriesByTitle[$title];
                }
            );

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(self::CATEGORY_CLASS)
            ->willReturn($this->categoryRepository);
    }

    /**
     * @return Product
     */
    protected function getPreparedProduct()
    {
        $sku = uniqid('', true);

        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1, 'sku' => $sku]);

        $category = new Category();
        $title = new LocalizedFallbackValue();
        $category->addTitle($title);
        $category->addProduct($product);

        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $category;

        return $product;
    }

    /**
     * @param string $title
     * @return string
     */
    protected function prepareTitle($title)
    {
        $this->findByDefaultTitleCalls[$title] = 0;

        $category = new Category();
        $fallbackValue = new LocalizedFallbackValue();
        $category->addTitle($fallbackValue);

        $this->categoriesByTitle[$title] = $category;

        return $title;
    }
}
