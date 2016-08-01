<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Owner;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Component\TestUtils\ORM\Mocks\ConnectionMock;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Owner\FrontendOwnerTreeProvider;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class FrontendOwnerTreeProviderTest extends OrmTestCase
{
    const ENTITY_NAMESPACE = 'OroB2B\Bundle\AccountBundle\Tests\Unit\Owner\Fixtures\Entity';

    const ORG_1 = 1;
    const ORG_2 = 2;

    const MAIN_ACCOUNT_1 = 10;
    const MAIN_ACCOUNT_2 = 20;
    const ACCOUNT_1 = 30;
    const ACCOUNT_2 = 40;
    const ACCOUNT_2_1 = 50;

    const USER_1 = 100;
    const USER_2 = 200;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /** @var EntityManagerMock */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendOwnershipMetadataProvider
     */
    protected $ownershipMetadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

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

        $this->ownershipMetadataProvider =
            $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getBasicLevelClass')
            ->willReturn(self::ENTITY_NAMESPACE . '\TestAccountUser');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getLocalLevelClass')
            ->willReturn(self::ENTITY_NAMESPACE . '\TestAccount');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [
                        'orob2b_account.owner.frontend_ownership_tree_provider.cache',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->cache,
                    ],
                    [
                        'orob2b_account.owner.frontend_ownership_metadata_provider',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->ownershipMetadataProvider,
                    ],
                    ['doctrine', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $doctrine],
                    [
                        'oro_security.security_facade',
                        ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                        $this->securityFacade,
                    ],
                ]
            );

        $this->treeProvider = new FrontendOwnerTreeProvider();
        $this->treeProvider->setContainer($this->container);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $conn
     * @param int                                      $expectsAt
     * @param string                                   $sql
     * @param array                                    $result
     */
    protected function setQueryExpectationAt(
        \PHPUnit_Framework_MockObject_MockObject $conn,
        $expectsAt,
        $sql,
        $result
    ) {
        $stmt = $this->createFetchStatementMock($result);
        $conn
            ->expects($this->at($expectsAt))
            ->method('query')
            ->with($sql)
            ->will($this->returnValue($stmt));
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
        $stmt = $this->getMock('Oro\Component\TestUtils\ORM\Mocks\StatementMock');
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
     * @param string[]                                 $existingTables
     */
    protected function setTablesExistExpectation($connection, array $existingTables)
    {
        $this->setFetchAllQueryExpectationAt(
            $connection,
            0,
            $this->em->getConnection()->getDatabasePlatform()->getListTablesSQL(),
            $existingTables
        );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $connection
     * @param string[]                                 $accounts
     */
    protected function setGetAccountsExpectation($connection, array $accounts)
    {
        $queryResult = [];
        foreach ($accounts as $item) {
            $queryResult[] = [
                'id_0'   => $item['id'],
                'sclr_1' => $item['orgId'],
                'sclr_2' => $item['parentId'],
            ];
        }
        $this->setQueryExpectationAt(
            $connection,
            1,
            'SELECT t0_.id AS id_0, t0_.organization_id AS sclr_1, t0_.parent_id AS sclr_2,'
            . ' (CASE WHEN t0_.parent_id IS NULL THEN 0 ELSE 1 END) AS sclr_3'
            . ' FROM tbl_account t0_'
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
                'id_1'   => $item['orgId'],
                'sclr_2' => $item['accountId'],
            ];
        }
        $this->setQueryExpectationAt(
            $connection,
            2,
            'SELECT t0_.id AS id_0, t1_.id AS id_1, t0_.account_id AS sclr_2'
            . ' FROM tbl_account_user t0_'
            . ' INNER JOIN tbl_account_user_to_organization t2_ ON t0_.id = t2_.account_user_id'
            . ' INNER JOIN tbl_organization t1_ ON t1_.id = t2_.organization_id'
            . ' ORDER BY id_1 ASC',
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

    public function testAccountsWithoutOrganization()
    {
        $connection = $this->getDriverConnectionMock($this->em);
        $this->setTablesExistExpectation($connection, ['tbl_account_user']);
        // the accounts without parent should be at the top,
        // rest accounts are sorted by parent id
        $this->setGetAccountsExpectation(
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
                    'accountId' => self::MAIN_ACCOUNT_1,
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
    public function testAccountTree()
    {
        $connection = $this->getDriverConnectionMock($this->em);
        $this->setTablesExistExpectation($connection, ['tbl_account_user']);
        // the accounts without parent should be at the top,
        // rest accounts are sorted by parent id
        $this->setGetAccountsExpectation(
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
                    'accountId' => self::MAIN_ACCOUNT_1,
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
    public function testAccountTreeWhenChildAccountAreLoadedBeforeParentAccount()
    {
        $connection = $this->getDriverConnectionMock($this->em);
        $this->setTablesExistExpectation($connection, ['tbl_account_user']);
        // the accounts without parent should be at the top,
        // rest accounts are sorted by parent id
        $this->setGetAccountsExpectation(
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
                    'accountId' => self::MAIN_ACCOUNT_1,
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

    public function testUserDoesNotHaveParentAccount()
    {
        $connection = $this->getDriverConnectionMock($this->em);
        $this->setTablesExistExpectation($connection, ['tbl_account_user']);
        // the accounts without parent should be at the top,
        // rest accounts are sorted by parent id
        $this->setGetAccountsExpectation(
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
                    'accountId' => null,
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
        $connection = $this->getDriverConnectionMock($this->em);
        $this->setTablesExistExpectation($connection, ['tbl_account_user']);
        // the accounts without parent should be at the top,
        // rest accounts are sorted by parent id
        $this->setGetAccountsExpectation(
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
                    'accountId' => self::ACCOUNT_1,
                ],
                [
                    'orgId'     => self::ORG_2,
                    'userId'    => self::USER_2,
                    'accountId' => self::ACCOUNT_2,
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

    public function testSupports()
    {
        $this->securityFacade->expects($this->exactly(2))
            ->method('getLoggedUser')
            ->willReturnOnConsecutiveCalls(new AccountUser(), new User());

        $this->assertTrue($this->treeProvider->supports());
        $this->assertFalse($this->treeProvider->supports());
    }
}
