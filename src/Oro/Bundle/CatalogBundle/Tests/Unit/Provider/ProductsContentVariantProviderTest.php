<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Provider\ProductsContentVariantProvider;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ProductsContentVariantProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductsContentVariantProvider
     */
    protected $provider;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilderMock;

    /**
     * @var Expr|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $exprMock;

    public function setUp()
    {
        $this->provider = new ProductsContentVariantProvider();

        $this->exprMock = $this->getMock(Expr::class);
        $this->exprMock->method('eq')->willReturn($this->exprMock);

        $this->queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->queryBuilderMock->method('expr')->willReturn($this->exprMock);
        $this->queryBuilderMock->method('leftJoin')->willReturn($this->queryBuilderMock);
        $this->queryBuilderMock->method('setParameter')->willReturn($this->queryBuilderMock);
    }

    public function testSupportedClass()
    {
        $this->assertTrue($this->provider->isSupportedClass(Product::class));
        $this->assertFalse($this->provider->isSupportedClass('Test'));
    }

    public function testModifyNodeQueryBuilderByEntities()
    {
        $this->queryBuilderMock->expects(self::atLeast(2))->method('leftJoin');
        $this->queryBuilderMock->expects(self::once())->method('setParameter');
        $this->queryBuilderMock->expects(self::once())->method('addSelect');
        $this->provider->modifyNodeQueryBuilderByEntities($this->queryBuilderMock, Product::class, []);
    }

    public function testGetRecordId()
    {
        $array['categoryProductId'] = 1;
        $this->assertEquals($array['categoryProductId'], $this->provider->getRecordId($array));
    }

    public function testGetLocalizedValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)->getMock();
        $expected = 0;
        $this->assertCount($expected, $this->provider->getLocalizedValues($node));
    }

    public function testGetValues()
    {
        $node = $this->getMockBuilder(ContentNodeInterface::class)->getMock();
        $expected = 0;
        $this->assertCount($expected, $this->provider->getValues($node));
    }
}
