<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendLocalizationBundle\Extension\SlugDetectLocalizationExtension;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization as LocalizationStub;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SlugDetectLocalizationExtensionTest extends TestCase
{
    private RequestStack|MockObject $requestStack;

    private UserLocalizationManagerInterface|MockObject $localizationManager;

    private ManagerRegistry|MockObject $registry;

    private SlugDetectLocalizationExtension $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->localizationManager = $this->createMock(UserLocalizationManagerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->extension = new SlugDetectLocalizationExtension(
            $this->requestStack,
            $this->localizationManager,
            $this->registry
        );
    }

    public function testGetCurrentLocalizationNoRequests(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(null);

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalization(): void
    {
        $localization = new LocalizationStub();
        $localization->setId(333);
        $usedSlug = (new Slug())->setLocalization($localization);
        $request = new Request([], [], ['_used_slug' => $usedSlug]);

        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->refreshSlug($usedSlug);
        $this->assertLocalizationEnabled($localization);

        $this->assertSame($localization, $this->extension->getCurrentLocalization());
        // local cache
        $this->assertSame($localization, $this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationIsNotEnabled(): void
    {
        $localization = new LocalizationStub();
        $localization->setId(555);
        $usedSlug = (new Slug())->setLocalization($localization);
        $request = new Request([], [], ['_used_slug' => $usedSlug]);

        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->refreshSlug($usedSlug);
        $this->assertLocalizationDisabled($localization);

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationFromNonLocalizedSlug(): void
    {
        $usedSlug = new Slug();
        $request = new Request([], [], ['_used_slug' => $usedSlug]);

        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->refreshSlug($usedSlug);

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    private function refreshSlug(Slug $usedSlug): void
    {
        $manager = $this->createMock(EntityManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
    }

    private function assertLocalizationEnabled(LocalizationStub $localization): void
    {
        $enabledLocalizations = [$localization->getId() => $localization];
        $this->localizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn($enabledLocalizations);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function assertLocalizationDisabled(LocalizationStub $localization): void
    {
        $this->localizationManager->expects($this->once())
            ->method('getEnabledLocalizations')
            ->willReturn([]);
    }
}
