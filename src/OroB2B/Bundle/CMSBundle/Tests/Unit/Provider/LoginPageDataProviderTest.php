<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;
use OroB2B\Bundle\CMSBundle\Provider\LoginPageDataProvider;

class LoginPageDataProviderTest extends \PHPUnit_Framework_TestCase
{
    const LOGIN_PAGE_CLASS = 'OroB2B\Bundle\CMSBundle\Entity\LoginPage';
    /**
     * @var LoginPageDataProvider
     */
    protected $provider;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    public function setUp()
    {
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->provider = new LoginPageDataProvider($this->managerRegistry);
        $this->provider->setLoginPageClass(self::LOGIN_PAGE_CLASS);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_cms_login_page', $this->provider->getIdentifier());
    }

    public function testGetData()
    {
        $loginPage = new LoginPage();

        /** @var ContextInterface $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

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

        $this->assertEquals($loginPage, $this->provider->getData($context));
        $this->assertEquals($loginPage, $this->provider->getData($context));
    }
}
