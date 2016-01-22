<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Menu;

use Knp\Menu\MenuFactory;
use OroB2B\Bundle\AccountBundle\Menu\AclExtension;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Doctrine\Common\Cache\CacheProvider;

class AclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $accountUserProvider;

    /**
     * @var MenuFactory
     */
    protected $factory;

    /**
     * @var AclExtension
     */
    protected $factoryExtension;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var bool
     */
    protected $hasLoggedUser = true;

    protected function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->getMock();

        $this->accountUserProvider = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Security\AccountUserProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountUserProvider
            ->expects($this->any())
            ->method('hasLoggedUser')
            ->willReturn($this->hasLoggedUser);

        $this->factoryExtension = new AclExtension($this->router, $this->accountUserProvider);
        $this->factory = new MenuFactory();
        $this->factory->addExtension($this->factoryExtension);
    }

    public function testBuildOptions()
    {
        $this->
    }
}
