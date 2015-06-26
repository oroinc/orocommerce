<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadata;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FrontendOwnershipMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    const LOCAL_LEVEL = 'OroB2B\Bundle\CustomerBundle\Entity\Customer';
    const BASIC_LEVEL = 'OroB2B\Bundle\CustomerBundle\Entity\AccountUser';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var FrontendOwnershipMetadataProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnMap(
                [
                    ['OroB2BCustomerBundle:Customer', self::LOCAL_LEVEL],
                    ['OroB2BCustomerBundle:AccountUser', self::BASIC_LEVEL],
                    [self::LOCAL_LEVEL, self::LOCAL_LEVEL],
                    [self::BASIC_LEVEL, self::BASIC_LEVEL],
                ]
            );

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save'])
            ->getMockForAbstractClass();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->configProvider,
                        ],
                        [
                            'oro_security.owner.ownership_metadata_provider.cache',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->cache,
                        ],
                        [
                            'oro_entity.orm.entity_class_resolver',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->entityClassResolver,
                        ],
                        [
                            'oro_security.security_facade',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->securityFacade,
                        ],
                    ]
                )
            );

        $this->provider = new FrontendOwnershipMetadataProvider(
            [
                'local_level' => self::LOCAL_LEVEL,
                'basic_level' => self::BASIC_LEVEL,
            ]
        );
        $this->provider->setContainer($this->container);
    }

    protected function tearDown()
    {
        unset(
            $this->configProvider,
            $this->entityClassResolver,
            $this->cache,
            $this->provider,
            $this->container,
            $this->securityFacade
        );
    }

    public function testSetAccessLevelClasses()
    {
        $provider = new FrontendOwnershipMetadataProvider(
            [
                'local_level' => 'OroB2BCustomerBundle:Customer',
                'basic_level' => 'OroB2BCustomerBundle:AccountUser',
            ]
        );
        $provider->setContainer($this->container);

        $this->assertEquals(self::LOCAL_LEVEL, $provider->getLocalLevelClass());
        $this->assertEquals(self::BASIC_LEVEL, $provider->getBasicLevelClass());
    }

    public function testGetMetadataWithoutCache()
    {
        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config
            ->set('frontend_owner_type', 'USER')
            ->set('frontend_owner_field_name', 'test_field')
            ->set('frontend_owner_column_name', 'test_column');

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('SomeClass')
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('SomeClass')
            ->willReturn($config);

        $this->cache = null;

        $this->assertEquals(
            new FrontendOwnershipMetadata('USER', 'test_field', 'test_column'),
            $this->provider->getMetadata('SomeClass')
        );
    }

    public function testGetMetadataUndefinedClassWithCache()
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with('UndefinedClass')
            ->willReturn(false);
        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->cache->expects($this->at(0))
            ->method('fetch')
            ->with('UndefinedClass')
            ->willReturn(false);
        $this->cache->expects($this->at(2))
            ->method('fetch')
            ->with('UndefinedClass')
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('UndefinedClass', true);

        $metadata = new FrontendOwnershipMetadata();
        $providerWithCleanCache = clone $this->provider;

        // no cache
        $this->assertEquals($metadata, $this->provider->getMetadata('UndefinedClass'));

        // local cache
        $this->assertEquals($metadata, $this->provider->getMetadata('UndefinedClass'));

        // cache
        $this->assertEquals($metadata, $providerWithCleanCache->getMetadata('UndefinedClass'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method getSystemLevelClass() unsupported.
     */
    public function testGetSystemLevelClass()
    {
        $this->assertFalse($this->provider->getSystemLevelClass());
    }

    public function testGetGlobalLevelClass()
    {
        $this->assertFalse($this->provider->getGlobalLevelClass());
    }

    public function testGetLocalLevelClass()
    {
        $this->assertEquals(self::LOCAL_LEVEL, $this->provider->getLocalLevelClass());
    }

    public function testGetBasicLevelClass()
    {
        $this->assertEquals(self::BASIC_LEVEL, $this->provider->getBasicLevelClass());
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param object|null $user
     * @param bool $expectedResult
     */
    public function testSupports($user, $expectedResult)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expectedResult, $this->provider->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'incorrect user object' => [
                'securityFacadeUser' => new \stdClass(),
                'expectedResult' => false,
            ],
            'account user' => [
                'securityFacadeUser' => new AccountUser(),
                'expectedResult' => true,
            ],
            'user is not logged in' => [
                'securityFacadeUser' => null,
                'expectedResult' => false,
            ],
        ];
    }

    /**
     * @dataProvider owningEntityNamesDataProvider
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Array parameter $owningEntityNames must contains `local_level` and `basic_level` keys
     *
     * @param array $owningEntityNames
     */
    public function testSetAccessLevelClassesException(array $owningEntityNames)
    {
        $provider = new FrontendOwnershipMetadataProvider($owningEntityNames);
        $provider->setContainer($this->container);
    }

    /**
     * @return array
     */
    public function owningEntityNamesDataProvider()
    {
        return [
            [
                'owningEntityNames' => [],
            ],
            [
                'owningEntityNames' => [
                    'local_level' => 'AcmeBundle\Entity\Customer',
                ],
            ],
            [
                'owningEntityNames' => [
                    'basic_level' => 'AcmeBundle\Entity\User',
                ],
            ],
        ];
    }
}
