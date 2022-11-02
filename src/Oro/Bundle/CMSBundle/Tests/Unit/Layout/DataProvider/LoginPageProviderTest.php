<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\CMSBundle\Layout\DataProvider\LoginPageProvider;

class LoginPageProviderTest extends \PHPUnit\Framework\TestCase
{
    private const LOGIN_PAGE_CLASS = LoginPage::class;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var LoginPageProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->provider = new LoginPageProvider($this->managerRegistry);
        $this->provider->setLoginPageClass(self::LOGIN_PAGE_CLASS);
    }

    public function testGetDefaultLoginPage()
    {
        $loginPage = new LoginPage();

        $repository = $this->createMock(ObjectRepository::class);
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
