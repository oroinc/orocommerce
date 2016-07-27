<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Extension;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\FrontendLocalizationBundle\Extension\CurrentLocalizationExtension;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\Entity\User;

class CurrentLocalizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $token;

    /** @var UserLocalizationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationManager;

    /** @var CurrentLocalizationExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->token = $this->getMock(TokenInterface::class);

        $this->localizationManager = $this->getMockBuilder(UserLocalizationManager::class)
            ->disableoriginalConstructor()
            ->getMock();

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

        $this->localizationManager->expects($this->never())->method('getCurrentLocalization');

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
}
