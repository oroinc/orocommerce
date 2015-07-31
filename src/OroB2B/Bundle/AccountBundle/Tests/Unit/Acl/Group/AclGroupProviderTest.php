<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Acl\Group;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Acl\Group\AclGroupProvider;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AclGroupProviderTest extends \PHPUnit_Framework_TestCase
{
    const LOCAL_LEVEL = 'OroB2B\Bundle\AccountBundle\Entity\Account';
    const BASIC_LEVEL = 'OroB2B\Bundle\AccountBundle\Entity\AccountUser';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var AclGroupProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_security.security_facade')
            ->willReturn($this->securityFacade);

        $this->provider = new AclGroupProvider($this->container);
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->container, $this->provider);
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
                'expectedResult' => false
            ],
            'account user' => [
                'securityFacadeUser' => new AccountUser(),
                'expectedResult' => true
            ],
            'user is not logged in' => [
                'securityFacadeUser' => null,
                'expectedResult' => true
            ],
        ];
    }

    public function testGetGroup()
    {
        $this->assertEquals(AccountUser::SECURITY_GROUP, $this->provider->getGroup());
    }
}
