<?php
/**
 * Created by PhpStorm.
 * User: emgiezet <mmalecki@oroinc.com>
 * Date: 06.10.16
 * Time: 16:00
 */

namespace Oro\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;

use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\SearchRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;


class ProductVisibilityLimitedSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ID_FIELD = 'id';
    const TEST_ENTITY_CLASS = 'Product';
    const TEST_ENTITY_SEARCH_ALIAS = 'oro_product';
    const TEST_SEARCH_STRING = 'test_search_string';
    const TEST_FIRST_RESULT = 30;
    const TEST_MAX_RESULTS = 10;
    const TEST_BACKEND_PREFIX = '/admin';
    const TEST_RESULTS = [1,2,3,4];

    /**
     * @var array
     */
    protected $testProperties = ['name', 'sku'];

    /**
     * @var array
     */
    protected $testSearchConfig = [self::TEST_ENTITY_CLASS => ['alias' => self::TEST_ENTITY_SEARCH_ALIAS]];

    /**
     * @var Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexer;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $query;

    /**
     * @var Expr|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $expr;

    /**
     * @var Result|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchResult;
    /**
     * @var AclHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    /**
     * @var ProductManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productManager;

    /**
     * @var ProductVisibilityLimitedSearchHandler
     */
    protected $searchHandler;

    /**
     * @var SearchQueryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchQuery;

    /**
     * @var Result|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $results;

    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDsipatcher;

    /**
     * @var SearchRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchRepository;
    /**
     * @var Symfony\Component\DependencyInjection\Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;




    protected function setUp()
    {
        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->setMethods(['simpleSearch'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'getSearchQueryBuilder'])
            ->getMock();
        $this->entityRepository
            ->method('createQueryBuilder')->will($this->returnValue($this->queryBuilder));


        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(['getSingleIdentifierFieldName'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->atLeastOnce())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue(self::TEST_ID_FIELD));

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->setMethods(['getMetadataFor'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->atLeastOnce())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($metadata));

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'getMetadataFactory'])
            ->getMock();

        $this->entityManager->expects($this->atLeastOnce())
            ->method('getRepository')
            ->will($this->returnValue($this->entityRepository));

        $this->entityManager->expects($this->atLeastOnce())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($this->entityManager));

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['expr', 'getQuery', 'where'])
            ->getMock();


        $this->query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult', 'getAST'])
            ->getMockForAbstractClass();

        $this->expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->setMethods(['in'])
            ->getMock();

        $this->searchResult = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result')
            ->setMethods(['getElements'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->setMethods(['apply'])
            ->getMock();
        $this->results = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result')->disableOriginalConstructor()->setMethods(['getElements'])->getMock();
        $this->results->method('getElements')->willReturn(self::TEST_RESULTS);

        $this->searchQuery = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\SearchQueryInterface')
            ->disableOriginalConstructor()
            ->setMethods([
                'addSelect',
                'getSelect', 'getSelectAliases',  'getQuery', 'getSelectDataFields', 'setFrom', 'addWhere', 'setOrderBy', 'getSortBy',
                'execute', 'getResult', 'getTotalCount', 'getSortOrder', 'setFirstResult', 'getFirstResult', 'setMaxResults', 'getMaxResults' ])
            ->getMock();
        $this->searchQuery->method('getResult')->withAnyParameters()->willReturn($this->results);

        $this->eventDsipatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->disableOriginalConstructor()->getMock();
        $this->productManager = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Manager\ProductManager')->setConstructorArgs([$this->eventDsipatcher ])
            ->setMethods(['restrictQueryBuilder', 'restrictSearchQuery'])
            ->getMock();
        $this->productManager->method('restrictSearchQuery')->with($this->searchQuery)->willReturn($this->searchQuery);


        $this->searchRepository = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Search\ProductRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getProductSearchQuery'])
            ->getMock();
        $this->searchRepository->method('getProductSearchQuery')->withAnyParameters()->willReturn($this->searchQuery);




        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($fakeRequest);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])->getMock();
        $this->container->method('get')->willReturn($this->returnValue($requestStack));
        $this->container->method('getParameter')->willReturn(self::TEST_BACKEND_PREFIX);
        $this->frontendHelper = new FrontendHelper(self::TEST_BACKEND_PREFIX, $this->container);


        $this->searchHandler = new ProductVisibilityLimitedSearchHandler(
            self::TEST_ENTITY_CLASS,
            $this->testProperties,
            $requestStack,
            $this->productManager
        );

        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->managerRegistry);
        $this->searchHandler->initDoctrinePropertiesByEntityManager($this->entityManager);
        $this->searchHandler->setFrontendHelper($this->frontendHelper);
        $this->searchHandler->setSearchRepository($this->searchRepository);
        $this->searchHandler->initSearchIndexer($this->indexer, $this->testSearchConfig);
        $this->searchHandler->setAclHelper($this->aclHelper);
    }



    public function testConstructorAndInitialize()
    {
        $this->assertAttributeSame(
            $this->indexer,
            'indexer',
            $this->searchHandler
        );
        $this->assertAttributeEquals(
            self::TEST_ENTITY_CLASS,
            'entityName',
            $this->searchHandler
        );
        $this->assertAttributeEquals(
            self::TEST_ID_FIELD,
            'idFieldName',
            $this->searchHandler
        );
        $this->assertAttributeEquals(
            $this->testProperties,
            'properties',
            $this->searchHandler
        );
    }

    public function testGetProperties()
    {
        $this->assertEquals($this->testProperties, $this->searchHandler->getProperties());
    }

    public function testGetEntitName()
    {
        $this->assertEquals(self::TEST_ENTITY_CLASS, $this->searchHandler->getEntityName());
    }

    public function testSearchEntities()
    {
        $result = $this->searchHandler->search('test', 0, 10);

        $this->assertArrayHasKey('results', $result, 'Results key not found');
        $this->assertEquals(count($result['results']), count(self::TEST_RESULTS), sprintf('Search result should containe %d elements', count(self::TEST_RESULTS)));

    }

}