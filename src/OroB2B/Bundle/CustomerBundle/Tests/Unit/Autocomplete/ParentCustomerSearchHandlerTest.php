<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroB2B\Bundle\CustomerBundle\Autocomplete\ParentCustomerSearchHandler;

class ParentCustomerSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'TestEntity';

    /**
     * @var ParentCustomerSearchHandler
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
        $this->entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\Repository\CustomerRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory = $this->getMetaMocks();
        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($this->entityRepository));

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->will($this->returnValue($this->entityManager));
        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchHandler = new ParentCustomerSearchHandler(self::TEST_ENTITY_CLASS, ['name']);
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
     * @dataProvider queryWithoutSeparatorDataProvider
     * @param string $search
     */
    public function testSearchNewCustomer($search)
    {
        $page = 1;
        $perPage = 15;
        $queryString = $search . ';';

        $foundElements = [
            $this->getSearchItem(1),
            $this->getSearchItem(2)
        ];
        $resultData = [
            $this->getResultStub(1, 'test1'),
            $this->getResultStub(2, 'test2')
        ];
        $expectedResultData = [
            ['id' => 1, 'name' => 'test1'],
            ['id' => 2, 'name' => 'test2']
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
     * @dataProvider queryWithoutSeparatorDataProvider
     * @param string $search
     */
    public function testSearchExistingCustomer($search)
    {
        $page = 1;
        $perPage = 15;
        $customerId = 2;
        $queryString = $search . ';' . $customerId;

        $foundElements = [
            $this->getSearchItem(1),
            $this->getSearchItem($customerId)
        ];
        $resultData = [
            $this->getResultStub(1, 'test1')
        ];
        $expectedResultData = [
            ['id' => 1, 'name' => 'test1']
        ];
        $expectedIds = [1];

        $this->entityRepository->expects($this->once())
            ->method('getChildrenIds')
            ->with($this->aclHelper, $customerId)
            ->will($this->returnValue([]));

        $this->assertSearchCall($search, $page, $perPage, $foundElements, $resultData, $expectedIds);

        $searchResult = $this->searchHandler->search($queryString, $page, $perPage);
        $this->assertInternalType('array', $searchResult);
        $this->assertArrayHasKey('more', $searchResult);
        $this->assertArrayHasKey('results', $searchResult);
        $this->assertEquals($expectedResultData, $searchResult['results']);
    }

    /**
     * @dataProvider queryWithoutSeparatorDataProvider
     * @param string $search
     */
    public function testSearchExistingCustomerWithChildren($search)
    {
        $page = 1;
        $perPage = 15;
        $customerId = 2;
        $queryString = $search . ';' . $customerId;
        $foundElements = [
            $this->getSearchItem(1),
            $this->getSearchItem(3)
        ];
        $resultData = [
            $this->getResultStub(1, 'test1')
        ];
        $expectedResultData = [
            ['id' => 1, 'name' => 'test1']
        ];
        $expectedIds = [1];

        $this->entityRepository->expects($this->once())
            ->method('getChildrenIds')
            ->with($this->aclHelper, $customerId)
            ->will($this->returnValue([3]));

        $this->assertSearchCall($search, $page, $perPage, $foundElements, $resultData, $expectedIds);

        $searchResult = $this->searchHandler->search($queryString, $page, $perPage);
        $this->assertInternalType('array', $searchResult);
        $this->assertArrayHasKey('more', $searchResult);
        $this->assertArrayHasKey('results', $searchResult);
        $this->assertEquals($expectedResultData, $searchResult['results']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMetaMocks()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods(['getSingleIdentifierFieldName'])
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
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
     * @param int $id
     * @param string $name
     * @return \stdClass
     */
    protected function getResultStub($id, $name)
    {
        $result = new \stdClass();
        $result->id = $id;
        $result->name = $name;

        return $result;
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
        $searchResult = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result')
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult->expects($this->once())
            ->method('getElements')
            ->will($this->returnValue($foundElements));
        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->with($search, $page - 1, $perPage + 1, 'alias')
            ->will($this->returnValue($searchResult));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($resultData));

        $expr = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->getMock();
        $expr->expects($this->once())
            ->method('in')
            ->with('e.id', $expectedIds)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expr));
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with($expr)
            ->will($this->returnSelf());
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder, 'VIEW')
            ->will($this->returnValue($query));
        $this->entityRepository
            ->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        return $searchResult;
    }
}
