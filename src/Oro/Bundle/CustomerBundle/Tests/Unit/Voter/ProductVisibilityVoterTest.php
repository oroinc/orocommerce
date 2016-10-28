<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Voter;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Acl\Voter\ProductVisibilityVoter;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductVisibilityVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var TokenInterface
     */
    protected $currentToken;

    /**
     * @var ProductVisibilityVoter
     */
    protected $voter;

    /**
     *
     */
    public function setUp()
    {
        $this->frontendHelper = new FrontendHelper('admin', $this->getContainerMock());
        $this->currentToken = $this->getMock(TokenInterface::class);

        $this->voter = new ProductVisibilityVoter($this->getDoctrineHelperMock());
        $this->voter->setFrontendHelper($this->frontendHelper);
        $this->voter->setProductSearchRepository($this->getProductRepositoryMock());
        $this->voter->setClassName(Product::class);
    }

    /**
     *
     */
    public function testVoteAbstain()
    {
        $object     = null;
        $attributes = [ProductVisibilityVoter::ATTRIBUTE_VIEW];
        $vote       = $this->voter->vote($this->currentToken, $object, $attributes);
        $this->assertEquals($vote, ProductVisibilityVoter::ACCESS_ABSTAIN);
    }

    /**
     *
     */
    public function testVoteGranted()
    {
        $attributes = [ProductVisibilityVoter::ATTRIBUTE_VIEW];
        $vote       = $this->voter->vote($this->currentToken, $this->getProductMock(), $attributes);
        $this->assertEquals($vote, ProductVisibilityVoter::ACCESS_GRANTED);
    }

    /**
     * @return Container|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getContainerMock()
    {
        $fakeRequestStack = new RequestStack();
        $fakeRequest      = new Request();
        $fakeRequestStack->push($fakeRequest);

        $container = $this->getMockBuilder(Container::class)->setMethods(['get', 'getParameter'])->getMock();

        $container->method('get')->willReturn($fakeRequestStack);
        $container->method('getParameter')->willReturn(true);

        return $container;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getProductMock()
    {
        $product = $this->getMockBuilder(Product::class)->setMethods(['getId'])->enableOriginalClone()->getMock();
        return $product;
    }

    /**
     * @return ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProductRepositoryMock()
    {
        $queryFactory = $this->getMockBuilder(QueryFactoryInterface::class)->disableOriginalConstructor()
            ->getMock();
        $queryFactory->method('create')->willReturn($this->getSearchQueryMock());

        $searchMappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()->getMock();

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->setConstructorArgs([$queryFactory, $searchMappingProvider])
            ->setMethods(['findOne'])->getMock();
        $productRepository->method('findOne')->willReturn($this->getProductMock());

        return $productRepository;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getSearchQueryMock()
    {
        $criteria = $this->getMockBuilder(Criteria::class)->setMethods(['andWhere'])->getMock();
        $query    = $this->getMockBuilder(Query::class)->setMethods(['from', 'select', 'getCriteria'])
            ->getMock();
        $query->method('getCriteria')->willReturn($criteria);

        $result = $this->getMockBuilder(Result::class)->setConstructorArgs([$query])->getMock();

        $searchQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->setMethods(['getQuery', 'getResult', 'execute',
                'getTotalCount', 'addSelect', 'addWhere', 'getCriteria', 'getFirstResult', 'getMaxResults',
                'getSelect', 'getSelectAliases', 'getSelectDataFields', 'getSortBy', 'getSortOrder', 'setFirstResult',
                'setFrom', 'setMaxResults', 'setOrderBy'])
            ->getMock();
        $searchQuery->method('getQuery')->willReturn($query);
        $searchQuery->method('getResult')->willReturn($result);

        return $searchQuery;
    }

    /**
     * @return DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getDoctrineHelperMock()
    {
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();
        $doctrineHelper->method('getEntityClass')->willReturn(Product::class);
        $doctrineHelper->method('getEntityIdentifier')->willReturn(1);
        $doctrineHelper->method('getSingleEntityIdentifier')->willReturn(1);

        return $doctrineHelper;
    }
}
