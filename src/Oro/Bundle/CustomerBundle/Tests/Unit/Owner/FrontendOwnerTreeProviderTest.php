<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\TestUtils\ORM\Mocks\ConnectionMock;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Owner\FrontendOwnerTreeProvider;

class FrontendOwnerTreeProviderTest extends OrmTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\CustomerBundle\Tests\Unit\Owner\Fixtures\Entity';

    const ORG_1 = 1;
    const ORG_2 = 2;

    const MAIN_ACCOUNT_1 = 10;
    const MAIN_ACCOUNT_2 = 20;
    const ACCOUNT_1 = 30;
    const ACCOUNT_2 = 40;
    const ACCOUNT_2_1 = 50;

    const USER_1 = 100;
    const USER_2 = 200;

    /** @var EntityManagerMock */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DatabaseChecker
     */
    protected $databaseChecker;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MetadataProviderInterface
     */
    protected $ownershipMetadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var FrontendOwnerTreeProvider
     */
    protected $treeProvider;

    protected function setUp()
    {
        $reader = new AnnotationReader();
        $metadataDriver = new AnnotationDriver($reader, self::ENTITY_NAMESPACE);

        $conn = new ConnectionMock([], new DriverMock());
        $conn->setDatabasePlatform(new MySqlPlatform());
        $this->em = $this->getTestEntityManager($conn);
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(['Test' => self::ENTITY_NAMESPACE]);

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->databaseChecker = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\DatabaseChecker')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            [],
            '',
            true,
            true,
            true,
            ['fetch', 'save']
        );
        $this->cache->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue(false));
        $this->cache->expects($this->any())
            ->method('save');

        $this->ownershipMetadataProvider = $this->createMock(
            'Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface'
        );
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getBasicLevelClass')
            ->willReturn(self::ENTITY_NAMESPACE . '\TestCustomerUser');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getLocalLevelClass')
            ->willReturn(self::ENTITY_NAMESPACE . '\TestCustomer');

        $this->tokenStorage = $this->createMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $this->treeProvider = new FrontendOwnerTreeProvider(
            $doctrine,
            $this->databaseChecker,
            $this->cache,
            $this->ownershipMetadataProvider,
            $this->tokenStorage
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $conn
     * @param int                                      $expectsAt
     * @param string                                   $sql
     * @param array                                    $result
     */
    protected function setFetchAllQueryExpectationAt(
        \PHPUnit_Framework_MockObject_MockObject $conn,
        $expectsAt,
        $sql,
        $result
    ) {
        $stmt = $this->createMock('Oro\Component\TestUtils\ORM\Mocks\StatementMock');
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn($result);
        $conn
            ->expects($this->at($expectsAt))
            ->method('query')
            ->with($sql)
            ->will($this->returnValue($stmt));
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $connection
     * @param string[]                                 $customers
     */
    protected function setGetCustomersExpectation($connection, array $customers)
    {
        $queryResult = [];
        foreach ($customers as $item) {
            $queryResult[] = [
                'id_0'   => $item['id'],
                'sclr_1' => $item['orgId'],
                'sclr_2' => $item['parentId'],
            ];
        }
        $this->setQueryExpectationAt(
            $connection,
            0,
            'SELECT t0_.id AS id_0, t0_.organization_id AS sclr_1, t0_.parent_id AS sclr_2,'
            . ' (CASE WHEN t0_.parent_id IS NULL THEN 0 ELSE 1 END) AS sclr_3'
            . ' FROM tbl_customer t0_'
            . ' ORDER BY sclr_3 ASC, sclr_2 ASC',
            $queryResult
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $connection
     * @param string[]                                 $users
     */
    protected function setGetUsersExpectation($connection, array $users)
    {
        $queryResult = [];
        foreach ($users as $item) {
            $queryResult[] = [
                'id_0'   => $item['userId'],
                'sclr_1'   => $item['orgId'],
                'sclr_2' => $item['customerId'],
            ];
        }
        $this->setQueryExpectationAt(
            $connection,
            1,
            'SELECT t0_.id AS id_0, t0_.organization_id AS sclr_1, t0_.customer_id AS sclr_2'
            . ' FROM tbl_customer_user t0_'
            . ' ORDER BY sclr_1 ASC',
            $queryResult
        );
    }

    /**
     * @param array     $expected
     * @param OwnerTree $actual
     */
    protected function assertOwnerTreeEquals(array $expected, OwnerTree $actual)
    {
        foreach ($expected as $property => $value) {
            $this->assertEquals(
                $value,
                $this->getObjectAttribute($actual, $property),
                'Owner Tree Property: ' . $property
            );
        }
    }

    public function testCustomersWithoutOrganization()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        $connection = $this->getDriverConnectionMock($this->em);
        // the customers without parent should be at the top,
        // rest customers are sorted by parent id
        $this->setGetCustomersExpectation(
            $connection,
            [
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => null,
                    'id'       => self::MAIN_ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_1,
                ],
                [
                    'orgId'    => null,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_2,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::ACCOUNT_2,
                    'id'       => self::ACCOUNT_2_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->setGetUsersExpectation(
            $connection,
            [
                [
                    'orgId'     => self::ORG_1,
                    'userId'    => self::USER_1,
                    'customerId' => self::MAIN_ACCOUNT_1,
                ],
            ]
        );

        $this->treeProvider->warmUpCache();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1]
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_ACCOUNT_1
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_ACCOUNT_1]
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_ACCOUNT_1]]
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_ACCOUNT_1 => self::ORG_1,
                    self::ACCOUNT_1      => self::ORG_1,
                    self::ACCOUNT_2_1    => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_ACCOUNT_1 => [self::USER_1],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_ACCOUNT_1 => [self::ACCOUNT_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_ACCOUNT_1, self::ACCOUNT_1, self::ACCOUNT_2_1]
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCustomerTree()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        $connection = $this->getDriverConnectionMock($this->em);
        // the customers without parent should be at the top,
        // rest customers are sorted by parent id
        $this->setGetCustomersExpectation(
            $connection,
            [
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => null,
                    'id'       => self::MAIN_ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_2,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::ACCOUNT_2,
                    'id'       => self::ACCOUNT_2_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->setGetUsersExpectation(
            $connection,
            [
                [
                    'orgId'     => self::ORG_1,
                    'userId'    => self::USER_1,
                    'customerId' => self::MAIN_ACCOUNT_1,
                ],
            ]
        );

        $this->treeProvider->warmUpCache();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1]
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_ACCOUNT_1
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_ACCOUNT_1]
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_ACCOUNT_1]]
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_ACCOUNT_1 => self::ORG_1,
                    self::ACCOUNT_1      => self::ORG_1,
                    self::ACCOUNT_2      => self::ORG_1,
                    self::ACCOUNT_2_1    => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_ACCOUNT_1 => [self::USER_1],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_ACCOUNT_1 => [self::ACCOUNT_2, self::ACCOUNT_2_1, self::ACCOUNT_1],
                    self::ACCOUNT_2      => [self::ACCOUNT_2_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_ACCOUNT_1, self::ACCOUNT_2, self::ACCOUNT_1, self::ACCOUNT_2_1]
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCustomerTreeWhenChildCustomerAreLoadedBeforeParentCustomer()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        $connection = $this->getDriverConnectionMock($this->em);
        // the customers without parent should be at the top,
        // rest customers are sorted by parent id
        $this->setGetCustomersExpectation(
            $connection,
            [
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => null,
                    'id'       => self::MAIN_ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::ACCOUNT_2,
                    'id'       => self::ACCOUNT_2_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_2,
                ],
            ]
        );
        // should be sorted by organization id
        $this->setGetUsersExpectation(
            $connection,
            [
                [
                    'orgId'     => self::ORG_1,
                    'userId'    => self::USER_1,
                    'customerId' => self::MAIN_ACCOUNT_1,
                ],
            ]
        );

        $this->treeProvider->warmUpCache();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1]
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::MAIN_ACCOUNT_1
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::MAIN_ACCOUNT_1]
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::MAIN_ACCOUNT_1]]
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_ACCOUNT_1 => self::ORG_1,
                    self::ACCOUNT_1      => self::ORG_1,
                    self::ACCOUNT_2      => self::ORG_1,
                    self::ACCOUNT_2_1    => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::MAIN_ACCOUNT_1 => [self::USER_1],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_ACCOUNT_1 => [self::ACCOUNT_1, self::ACCOUNT_2, self::ACCOUNT_2_1],
                    self::ACCOUNT_2      => [self::ACCOUNT_2_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_ACCOUNT_1, self::ACCOUNT_2_1, self::ACCOUNT_1, self::ACCOUNT_2]
                ],
            ],
            $tree
        );
    }

    public function testUserDoesNotHaveParentCustomer()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        $connection = $this->getDriverConnectionMock($this->em);
        // the customers without parent should be at the top,
        // rest customers are sorted by parent id
        $this->setGetCustomersExpectation(
            $connection,
            [
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => null,
                    'id'       => self::MAIN_ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->setGetUsersExpectation(
            $connection,
            [
                [
                    'orgId'     => self::ORG_1,
                    'userId'    => self::USER_1,
                    'customerId' => null,
                ],
            ]
        );

        $this->treeProvider->warmUpCache();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1],
                ],
                'userOwningBusinessUnitId'         => [],
                'userBusinessUnitIds'              => [],
                'userOrganizationBusinessUnitIds'  => [],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_ACCOUNT_1 => self::ORG_1,
                    self::ACCOUNT_1      => self::ORG_1,
                ],
                'assignedBusinessUnitUserIds'      => [],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_ACCOUNT_1 => [self::ACCOUNT_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_ACCOUNT_1, self::ACCOUNT_1],
                ],
            ],
            $tree
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSeveralOrganizations()
    {
        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        $connection = $this->getDriverConnectionMock($this->em);
        // the customers without parent should be at the top,
        // rest customers are sorted by parent id
        $this->setGetCustomersExpectation(
            $connection,
            [
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => null,
                    'id'       => self::MAIN_ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_2,
                    'parentId' => null,
                    'id'       => self::ACCOUNT_2,
                ],
                [
                    'orgId'    => self::ORG_1,
                    'parentId' => self::MAIN_ACCOUNT_1,
                    'id'       => self::ACCOUNT_1,
                ],
                [
                    'orgId'    => self::ORG_2,
                    'parentId' => self::ACCOUNT_2,
                    'id'       => self::ACCOUNT_2_1,
                ],
            ]
        );
        // should be sorted by organization id
        $this->setGetUsersExpectation(
            $connection,
            [
                [
                    'orgId'     => self::ORG_1,
                    'userId'    => self::USER_1,
                    'customerId' => self::ACCOUNT_1,
                ],
                [
                    'orgId'     => self::ORG_2,
                    'userId'    => self::USER_2,
                    'customerId' => self::ACCOUNT_2,
                ],
            ]
        );

        $this->treeProvider->warmUpCache();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        $this->assertOwnerTreeEquals(
            [
                'userOwningOrganizationId'         => [
                    self::USER_1 => self::ORG_1,
                    self::USER_2 => self::ORG_2,
                ],
                'userOrganizationIds'              => [
                    self::USER_1 => [self::ORG_1],
                    self::USER_2 => [self::ORG_2],
                ],
                'userOwningBusinessUnitId'         => [
                    self::USER_1 => self::ACCOUNT_1,
                    self::USER_2 => self::ACCOUNT_2,
                ],
                'userBusinessUnitIds'              => [
                    self::USER_1 => [self::ACCOUNT_1],
                    self::USER_2 => [self::ACCOUNT_2],
                ],
                'userOrganizationBusinessUnitIds'  => [
                    self::USER_1 => [self::ORG_1 => [self::ACCOUNT_1]],
                    self::USER_2 => [self::ORG_2 => [self::ACCOUNT_2]],
                ],
                'businessUnitOwningOrganizationId' => [
                    self::MAIN_ACCOUNT_1 => self::ORG_1,
                    self::ACCOUNT_1      => self::ORG_1,
                    self::ACCOUNT_2      => self::ORG_2,
                    self::ACCOUNT_2_1    => self::ORG_2,
                ],
                'assignedBusinessUnitUserIds'      => [
                    self::ACCOUNT_1 => [self::USER_1],
                    self::ACCOUNT_2 => [self::USER_2],
                ],
                'subordinateBusinessUnitIds'       => [
                    self::MAIN_ACCOUNT_1 => [self::ACCOUNT_1],
                    self::ACCOUNT_2      => [self::ACCOUNT_2_1],
                ],
                'organizationBusinessUnitIds'      => [
                    self::ORG_1 => [self::MAIN_ACCOUNT_1, self::ACCOUNT_1],
                    self::ORG_2 => [self::ACCOUNT_2, self::ACCOUNT_2_1],
                ],
            ],
            $tree
        );
    }

    public function testSupportsForSupportedUser()
    {
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->assertTrue($this->treeProvider->supports());
    }

    public function testSupportsForNotSupportedUser()
    {
        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(new User());

        $this->assertFalse($this->treeProvider->supports());
    }

    public function testSupportsWhenNoSecurityToken()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertFalse($this->treeProvider->supports());
    }
}
