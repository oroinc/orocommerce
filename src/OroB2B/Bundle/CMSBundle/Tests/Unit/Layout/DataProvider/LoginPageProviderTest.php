<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;
use OroB2B\Bundle\CMSBundle\Layout\DataProvider\LoginPageProvider;

class LoginPageProviderTest extends \PHPUnit_Framework_TestCase
{
    const LOGIN_PAGE_CLASS = 'OroB2B\Bundle\CMSBundle\Entity\LoginPage';
    /**
     * @var LoginPageProvider
     */
    protected $provider;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    public function setUp()
    {
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->provider = new LoginPageProvider($this->managerRegistry);
        $this->provider->setLoginPageClass(self::LOGIN_PAGE_CLASS);
    }

    public function testGetDefaultLoginPage()
    {
        $loginPage = new LoginPage();

        /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->willReturn($loginPage);

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with(self::LOGIN_PAGE_CLASS)
            ->willReturn($repository);

        $this->assertEquals($loginPage, $this->provider->getDefaultLoginPage());
    }
}
