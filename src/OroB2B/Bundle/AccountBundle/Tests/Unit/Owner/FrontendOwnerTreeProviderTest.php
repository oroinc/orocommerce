<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Owner;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Owner\FrontendOwnerTreeProvider;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class FrontendOwnerTreeProviderTest extends \PHPUnit_Framework_TestCase
{
    const ACCOUNT_USER_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';
    const CUSTOMER_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Account';

    const MAIN_CUSTOMER_ID = 1;
    const SECOND_CUSTOMER_ID = 2;
    const CHILD_CUSTOMER_ID = 3;
    const FIRST_USER_ID = 4;
    const SECOND_USER_ID = 5;
    const THIRD_USER_ID = 6;
    const ORGANIZATION_ID = 7;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

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
        $this->cache = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');
        $this->cache->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue(false));
        $this->cache->expects($this->any())
            ->method('save');

        $this->ownershipMetadataProvider =
            $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

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
                    ['doctrine', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->managerRegistry],
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

    public function testGetTree()
    {
        $accountUserRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $accountUserManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $accountUserManager->expects($this->any())
            ->method('getRepository')
            ->with(self::ACCOUNT_USER_CLASS)
            ->willReturn($accountUserRepository);

        $AccountRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $customerManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $customerManager->expects($this->any())
            ->method('getRepository')
            ->with(self::CUSTOMER_CLASS)
            ->willReturn($AccountRepository);

        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getBasicLevelClass')
            ->willReturn(self::ACCOUNT_USER_CLASS);
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getLocalLevelClass')
            ->willReturn(self::CUSTOMER_CLASS);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [self::ACCOUNT_USER_CLASS, $accountUserManager],
                [self::CUSTOMER_CLASS, $customerManager],
            ]);

        list($accountUsers, $customers) = $this->getTestData();

        $accountUserRepository->expects($this->any())
            ->method('findAll')
            ->will($this->returnValue($accountUsers));

        $AccountRepository->expects($this->any())
            ->method('findAll')
            ->will($this->returnValue($customers));

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $accountUserManager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $metadata->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue('test'));
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $accountUserManager->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $connection->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\MySqlSchemaManager')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager));
        $schemaManager->expects($this->any())
            ->method('listTableNames')
            ->will($this->returnValue(['test']));

        $this->treeProvider->warmUpCache();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();
        $this->assertTestData($tree);
    }

    /**
     * @param object $object
     * @param int $id
     */
    protected function setId($object, $id)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($object, $id);
    }

    /**
     * @return array
     */
    protected function getTestData()
    {
        $organization = new Organization();
        $this->setId($organization, self::ORGANIZATION_ID);

        $mainCustomer = new Account();
        $this->setId($mainCustomer, self::MAIN_CUSTOMER_ID);
        $mainCustomer->setOrganization($organization);

        $secondCustomer = new Account();
        $this->setId($secondCustomer, self::SECOND_CUSTOMER_ID);
        $secondCustomer->setOrganization($organization);

        $childCustomer = new Account();
        $this->setId($childCustomer, self::CHILD_CUSTOMER_ID);
        $childCustomer->setOrganization($organization);
        $childCustomer->setParent($mainCustomer);

        $firstUser = new AccountUser();
        $this->setId($firstUser, self::FIRST_USER_ID);
        $firstUser->setAccount($mainCustomer);
        $firstUser->setOrganizations(new ArrayCollection([$organization]));

        $secondUser = new AccountUser();
        $this->setId($secondUser, self::SECOND_USER_ID);
        $secondUser->setAccount($secondCustomer);
        $secondUser->setOrganizations(new ArrayCollection([$organization]));

        $thirdUser = new AccountUser();
        $this->setId($thirdUser, self::THIRD_USER_ID);
        $thirdUser->setAccount($childCustomer);
        $thirdUser->setOrganizations(new ArrayCollection([$organization]));

        return [
            [$firstUser, $secondUser, $thirdUser],
            [$mainCustomer, $secondCustomer, $childCustomer]
        ];
    }

    /**
     * @param OwnerTree $tree
     */
    protected function assertTestData(OwnerTree $tree)
    {
        foreach ([self::MAIN_CUSTOMER_ID, self::SECOND_CUSTOMER_ID, self::CHILD_CUSTOMER_ID] as $customerId) {
            $this->assertEquals(self::ORGANIZATION_ID, $tree->getBusinessUnitOrganizationId($customerId));
        }

        $this->assertEquals([self::CHILD_CUSTOMER_ID], $tree->getSubordinateBusinessUnitIds(self::MAIN_CUSTOMER_ID));
        $this->assertEmpty($tree->getSubordinateBusinessUnitIds(self::SECOND_CUSTOMER_ID));
        $this->assertEmpty($tree->getSubordinateBusinessUnitIds(self::CHILD_CUSTOMER_ID));

        foreach ([self::FIRST_USER_ID, self::SECOND_USER_ID, self::THIRD_USER_ID] as $userId) {
            $this->assertEquals(self::ORGANIZATION_ID, $tree->getUserOrganizationId($userId));
        }

        $this->assertEquals(self::MAIN_CUSTOMER_ID, $tree->getUserBusinessUnitId(self::FIRST_USER_ID));
        $this->assertEquals(self::SECOND_CUSTOMER_ID, $tree->getUserBusinessUnitId(self::SECOND_USER_ID));
        $this->assertEquals(self::CHILD_CUSTOMER_ID, $tree->getUserBusinessUnitId(self::THIRD_USER_ID));

        $this->assertEquals(
            [self::MAIN_CUSTOMER_ID],
            $tree->getUserBusinessUnitIds(self::FIRST_USER_ID, self::ORGANIZATION_ID)
        );
        $this->assertEquals(
            [self::SECOND_CUSTOMER_ID],
            $tree->getUserBusinessUnitIds(self::SECOND_USER_ID, self::ORGANIZATION_ID)
        );
        $this->assertEquals(
            [self::CHILD_CUSTOMER_ID],
            $tree->getUserBusinessUnitIds(self::THIRD_USER_ID, self::ORGANIZATION_ID)
        );

        $undefinedOrganization = 42;
        $this->assertEmpty($tree->getUserBusinessUnitIds(self::FIRST_USER_ID, $undefinedOrganization));
        $this->assertEmpty($tree->getUserBusinessUnitIds(self::SECOND_USER_ID, $undefinedOrganization));
        $this->assertEmpty($tree->getUserBusinessUnitIds(self::THIRD_USER_ID, $undefinedOrganization));
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
