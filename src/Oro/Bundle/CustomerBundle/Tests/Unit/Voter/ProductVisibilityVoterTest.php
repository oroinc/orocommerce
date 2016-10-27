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
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\Result\Item;

class ProductVisibilityVoterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var TokenInterface
     */
    protected $currentToken;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var QueryFactoryInterface
     */
    protected $queryFactory;

    /**
     * @var SearchMappingProvider
     */
    protected $searchMappingProvider;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @var SearchQueryInterface
     */
    protected $searchQuery;

    /**
     * @var Result
     */
    protected $result;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Product
     */
    protected $product;

    public function setUp()
    {
        $fakeRequestStack = new RequestStack();
        $fakeRequest      = new Request();
        $fakeRequestStack->push($fakeRequest);

        $this->container = $this->getMockBuilder(Container::class)->setMethods(['get', 'getParameter'])->getMock();

        $this->container->method('get')->willReturn($fakeRequestStack);
        $this->container->method('getParameter')->willReturn(true);

        $this->frontendHelper = new FrontendHelper('admin', $this->container);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->method('getEntityClass')->willReturn(Product::class);
        $this->doctrineHelper->method('getEntityIdentifier')->willReturn(1);
        $this->doctrineHelper->method('getSingleEntityIdentifier')->willReturn(1);

        $this->criteria = $this->getMockBuilder(Criteria::class)->setMethods(['andWhere'])->getMock();
        $this->query    = $this->getMockBuilder(Query::class)->setMethods(['from', 'select', 'getCriteria'])
            ->getMock();
        $this->query->method('getCriteria')->willReturn($this->criteria);

        $this->result = $this->getMockBuilder(Result::class)->setConstructorArgs([$this->query])->getMock();

        $this->searchQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->setMethods(['getQuery', 'getResult', 'execute',
                'getTotalCount', 'addSelect', 'addWhere', 'getCriteria', 'getFirstResult', 'getMaxResults',
                'getSelect', 'getSelectAliases', 'getSelectDataFields', 'getSortBy', 'getSortOrder', 'setFirstResult',
                'setFrom', 'setMaxResults', 'setOrderBy'])
            ->getMock();

        $this->searchQuery->method('getQuery')->willReturn($this->query);
        $this->searchQuery->method('getResult')->willReturn($this->result);

        $this->queryFactory = $this->getMockBuilder(QueryFactoryInterface::class)->disableOriginalConstructor()
            ->getMock();
        $this->queryFactory->method('create')->willReturn($this->searchQuery);

        $this->searchMappingProvider = $this->getMockBuilder(AbstractSearchMappingProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->product = $this->getMockBuilder(Product::class)->setMethods(['getId'])->enableOriginalClone()->getMock();
        $this->product->method('getIdentifier')->willReturn(1);

        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->setConstructorArgs([$this->queryFactory, $this->searchMappingProvider])
            ->setMethods(['searchFilteredBySkus', 'findOne'])->getMock();
        $this->productRepository->method('findOne')->willReturn($this->product);

        $this->item = $this->getMock(Item::class);
        $this->productRepository->method('findOne')->willReturn($this->product);
        $this->currentToken = $this->getMock(TokenInterface::class);
    }

    public function testVote()
    {
        $voter = new ProductVisibilityVoter($this->doctrineHelper);
        $voter->setFrontendHelper($this->frontendHelper);
        $voter->setProductSearchRepository($this->productRepository);
        $voter->setClassName(Product::class);
        $object     = null;
        $attributes = [ProductVisibilityVoter::ATTRIBUTE_VIEW];
        $vote       = $voter->vote($this->currentToken, $object, $attributes);
        $this->assertEquals($vote, ProductVisibilityVoter::ACCESS_ABSTAIN);

        $attributes = [ProductVisibilityVoter::ATTRIBUTE_VIEW];
        $vote       = $voter->vote($this->currentToken, $this->product, $attributes);
        $this->assertEquals($vote, ProductVisibilityVoter::ACCESS_GRANTED);
    }
}
