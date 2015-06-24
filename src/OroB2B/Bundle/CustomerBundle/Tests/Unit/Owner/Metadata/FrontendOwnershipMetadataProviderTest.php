<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadata;
use OroB2B\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class FrontendOwnershipMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    const LOCAL_LEVEL = 'OroB2B\Bundle\CustomerBundle\Entity\Customer';
    const BASIC_LEVEL = 'OroB2B\Bundle\CustomerBundle\Entity\AccountUser';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected $cache;

    /**
     * @var FrontendOwnershipMetadataProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save'])
            ->getMockForAbstractClass();

        $this->provider = new FrontendOwnershipMetadataProvider(
            [
                'local_level' => self::LOCAL_LEVEL,
                'basic_level' => self::BASIC_LEVEL,
            ],
            $this->securityFacade,
            $this->configProvider,
            $this->cache
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->configProvider, $this->cache, $this->provider);
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
        $this->provider->getSystemLevelClass();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method getGlobalLevelClass() unsupported.
     */
    public function testGetGlobalLevelClass()
    {
        $this->provider->getGlobalLevelClass();
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
     * @param object $user
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
                'securityFacade' => new \stdClass(),
                'expectedResult' => false,
            ],
            'account user' => [
                'securityFacade' => new AccountUser(),
                'expectedResult' => true,
            ],
        ];
    }
}
