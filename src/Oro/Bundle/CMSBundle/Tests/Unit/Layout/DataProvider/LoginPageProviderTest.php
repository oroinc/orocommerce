<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\CMSBundle\Layout\DataProvider\LoginPageProvider;

class LoginPageProviderTest extends \PHPUnit\Framework\TestCase
{
    const LOGIN_PAGE_CLASS = 'Oro\Bundle\CMSBundle\Entity\LoginPage';
    /**
     * @var LoginPageProvider
     */
    protected $provider;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $managerRegistry;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->provider = new LoginPageProvider($this->managerRegistry);
        $this->provider->setLoginPageClass(self::LOGIN_PAGE_CLASS);
    }

    public function testGetDefaultLoginPage()
    {
        $loginPage = new LoginPage();

        /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');
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
