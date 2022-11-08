<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Extension;

use Oro\Bundle\FrontendLocalizationBundle\Extension\CurrentLocalizationExtension;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CurrentLocalizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var CurrentLocalizationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->localizationManager = $this->createMock(UserLocalizationManager::class);

        $this->extension = new CurrentLocalizationExtension($this->tokenStorage, $this->localizationManager);
    }

    public function testGetCurrentLocalizationAndUser()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $this->token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());

        $this->localizationManager->expects($this->never())
            ->method('getCurrentLocalization');

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($this->token);

        $this->token->expects($this->once())
            ->method('getUser')
            ->willReturn(new \stdClass());

        $this->localizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithoutToken()
    {
        $localization = new Localization();

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->token->expects($this->never())
            ->method($this->anything());

        $this->localizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->extension->getCurrentLocalization());
    }
}
