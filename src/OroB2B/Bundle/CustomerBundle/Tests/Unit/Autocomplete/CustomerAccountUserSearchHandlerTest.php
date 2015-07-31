<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroB2B\Bundle\CustomerBundle\Autocomplete\CustomerAccountUserSearchHandler;

class CustomerAccountUserSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const DELIMITER = ';';

    const TEST_ENTITY_CLASS = 'TestAccountUserEntity';

    /**
     * @var CustomerAccountUserSearchHandler
     */
    protected $searchHandler;

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
     * @var Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexer;

    /**
     * @var AclHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory = $this->getMetaMocks();
        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $this->entityRepository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->entityRepository);

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->entityManager);
        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchHandler = new CustomerAccountUserSearchHandler(self::TEST_ENTITY_CLASS, ['email']);
        $this->searchHandler->initSearchIndexer($this->indexer, [self::TEST_ENTITY_CLASS => ['alias' => 'alias']]);
        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->managerRegistry);
        $this->searchHandler->setAclHelper($this->aclHelper);
    }

    /**
     * @dataProvider queryWithoutSeparatorDataProvider
     * @param string $query
     */
    public function testSearchNoSeparator($query)
    {
        $this->indexer->expects($this->never())
            ->method($this->anything());
        $result = $this->searchHandler->search($query, 1, 10);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('more', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertFalse($result['more']);
        $this->assertEmpty($result['results']);
    }

    /**
     * @return array
     */
    public function queryWithoutSeparatorDataProvider()
    {
        return [
            [''],
            ['test']
        ];
    }

    /**
     * @return array
     */
    public function queryFullDataProvider()
    {
        return [
            [1, ''],
            [2, 'test2'],
        ];
    }

    /**
     * @dataProvider queryWithoutSeparatorDataProvider
     * @param string $search
     */
    public function testSearchEmptyCustomer($search)
    {
        $page = 1;
        $perPage = 15;
        $queryString = self::DELIMITER . $search;

        $foundElements = [
            $this->getSearchItem(1),
            $this->getSearchItem(2)
        ];
        $resultData = [
            $this->getResultStub(1, 'test1'),
            $this->getResultStub(2, 'test2')
        ];
        $expectedResultData = [
            ['id' => 1, 'email' => 'test1'],
            ['id' => 2, 'email' => 'test2']
        ];
        $expectedIds = [1, 2];

        $this->assertSearchCall($search, $page, $perPage, $foundElements, $resultData, $expectedIds);

        $searchResult = $this->searchHandler->search($queryString, $page, $perPage);
        $this->assertInternalType('array', $searchResult);
        $this->assertArrayHasKey('more', $searchResult);
        $this->assertArrayHasKey('results', $searchResult);
        $this->assertEquals($expectedResultData, $searchResult['results']);
    }


    /**
     * @dataProvider queryFullDataProvider
     * @param int $customerId
     * @param string $search
     */
    public function testSearchWithCustomer($customerId, $search)
    {
        $page = 1;
        $perPage = 15;
        $queryString = sprintf('%d%s%s', $customerId, self::DELIMITER, $search);

        $foundElements = [
            $this->getSearchItem($customerId)
        ];
        $resultData = [
            $this->getResultStub($customerId, 'test1')
        ];
        $expectedResultData = [
            ['id' => $customerId, 'email' => 'test1'],
        ];
        $expectedIds = [$customerId];
        $this->assertSearchCall($search, $page, $perPage, $foundElements, $resultData, $expectedIds);

        $searchResult = $this->searchHandler->search($queryString, $page, $perPage);

        $this->assertInternalType('array', $searchResult);
        $this->assertArrayHasKey('more', $searchResult);
        $this->assertArrayHasKey('results', $searchResult);
        $this->assertEquals($expectedResultData, $searchResult['results']);
    }

    /**
     * @param int $id
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSearchItem($id)
    {
        $element = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();
        $element->expects($this->once())
            ->method('getRecordId')
            ->will($this->returnValue($id));

        return $element;
    }

    /**
     * @return ClassMetadataFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetaMocks()
    {
        /* @var $metadata ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(['getSingleIdentifierFieldName'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        /* @var $metadataFactory ClassMetadataFactory|\PHPUnit_Framework_MockObject_MockObject */
        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->setMethods(['getMetadataFor'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($metadata));

        return $metadataFactory;
    }

    /**
     * @param string $search
     * @param int $page
     * @param int $perPage
     * @param array $foundElements
     * @param array $resultData
     * @param array $expectedIds
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function assertSearchCall(
        $search,
        $page,
        $perPage,
        array $foundElements,
        array $resultData,
        array $expectedIds
    ) {
        /* @var $searchResult Result|\PHPUnit_Framework_MockObject_MockObject */
        $searchResult = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result')
            ->disableOriginalConstructor()
            ->getMock();

        $searchResult->expects($this->once())
            ->method('getElements')
            ->willReturn($foundElements);

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with($search, $page - 1, $perPage + 1, 'alias')
            ->willReturn($searchResult);

        /* @var $queryBuilder QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $query AbstractQuery|\PHPUnit_Framework_MockObject_MockObject */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($resultData);

        /* @var $expr Expr|\PHPUnit_Framework_MockObject_MockObject */
        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();

        $expr->expects($this->once())
            ->method('in')
            ->with('e.id', $expectedIds)
            ->will($this->returnSelf());

        $expr->expects($this->once())
            ->method('asc')
            ->with('e.email')
            ->will($this->returnSelf());

        $queryBuilder->expects($this->exactly(2))
            ->method('expr')
            ->willReturn($expr);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with($expr)
            ->will($this->returnSelf());

        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with($expr)
            ->will($this->returnSelf());

        $queryBuilder->expects($this->any())
            ->method('andWhere')
            ->with('e.customer = :account')
            ->will($this->returnSelf());

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder, 'VIEW')
            ->willReturn($query);

        $this->entityRepository
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        return $searchResult;
    }

    /**
     * @param int $id
     * @param string $email
     * @return \stdClass
     */
    protected function getResultStub($id, $email)
    {
        $result = new \stdClass();
        $result->id = $id;
        $result->email = $email;

        return $result;
    }
}
