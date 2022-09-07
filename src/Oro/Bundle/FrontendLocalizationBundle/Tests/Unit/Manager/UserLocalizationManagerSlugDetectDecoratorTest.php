<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\FrontendLocalizationBundle\DependencyInjection\Configuration;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerInterface;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManagerSlugDetectDecorator;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserLocalizationManagerSlugDetectDecoratorTest extends TestCase
{
    /**
     * @var UserLocalizationManagerInterface|MockObject
     */
    private $innerManager;

    /**
     * @var RequestStack|MockObject
     */
    private $requestStack;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var WebsiteManager|MockObject
     */
    private $websiteManager;

    private UserLocalizationManagerSlugDetectDecorator $manager;

    protected function setUp(): void
    {
        $this->innerManager = $this->createMock(UserLocalizationManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->manager = new UserLocalizationManagerSlugDetectDecorator(
            $this->innerManager,
            $this->requestStack,
            $this->registry,
            $this->configManager,
            $this->websiteManager
        );
    }

    public function testGetEnabledLocalizations(): void
    {
        $localizations = [new LocalizationStub()];

        $this->innerManager->expects(self::once())
            ->method('getEnabledLocalizations')
            ->willReturn($localizations);

        self::assertEquals($localizations, $this->manager->getEnabledLocalizations());
    }

    public function testGetDefaultLocalization(): void
    {
        $localization = new LocalizationStub();

        $this->innerManager->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        self::assertEquals($localization, $this->manager->getDefaultLocalization());
    }

    public function testGetCurrentLocalizationNotSupport(): void
    {
        $this->assertIfSupported(false);

        $website = new WebsiteStub(1);
        $localization = new LocalizationStub();

        $this->innerManager->expects(self::once())
            ->method('getCurrentLocalization')
            ->with($website)
            ->willReturn($localization);

        $this->requestStack->expects(self::never())
            ->method('getMasterRequest');

        self::assertEquals($localization, $this->manager->getCurrentLocalization($website));
    }

    public function testGetCurrentLocalizationNotUsingSlug(): void
    {
        $this->assertIfSupported();

        $website = new WebsiteStub(1);
        $localization = new LocalizationStub();
        $request = new Request();

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);
        $this->innerManager->expects(self::once())
            ->method('getCurrentLocalization')
            ->with($website)
            ->willReturn($localization);

        self::assertEquals($localization, $this->manager->getCurrentLocalization($website));
    }

    public function testGetCurrentLocalizationButSlugLocalizationIsEmpty(): void
    {
        $this->assertIfSupported();

        $website = new WebsiteStub(1);
        $localization = new LocalizationStub();
        $usedSlug = (new Slug())->setUrl('/slug1');
        $request = new Request([], [], ['_used_slug' => $usedSlug]);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);
        $this->refreshSlug($usedSlug);
        $this->innerManager->expects(self::once())
            ->method('getCurrentLocalization')
            ->with($website)
            ->willReturn($localization);

        self::assertEquals($localization, $this->manager->getCurrentLocalization($website));
    }

    public function testGetCurrentLocalizationFromSlugLocalization(): void
    {
        $this->assertIfSupported();

        $website = new WebsiteStub(1);
        $localization = new LocalizationStub();
        $usedSlug = (new Slug())->setUrl('/slug1')->setLocalization($localization);
        $request = new Request([], [], ['_used_slug' => $usedSlug]);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);
        $this->refreshSlug($usedSlug);

        $this->innerManager->expects(self::once())
            ->method('getEnabledLocalizations')
            ->willReturn([$localization->getId() => $localization]);

        self::assertEquals($localization, $this->manager->getCurrentLocalization($website));
    }

    public function tesGetCurrentLocalizationByCustomerUser(): void
    {
        $customerUser = new CustomerUserStub();
        $website = new WebsiteStub(1);
        $localization = new LocalizationStub();

        $this->innerManager->expects(self::once())
            ->method('getCurrentLocalizationByCustomerUser')
            ->with($customerUser, $website)
            ->willReturn($localization);

        self::assertEquals(
            $localization,
            $this->manager->getCurrentLocalizationByCustomerUser($customerUser, $website)
        );
    }

    public function testSetCurrentLocalization(): void
    {
        $website = new WebsiteStub(1);
        $localization = new LocalizationStub();

        $this->innerManager->expects(self::once())
            ->method('setCurrentLocalization')
            ->with($localization, $website);

        $this->manager->setCurrentLocalization($localization, $website);
    }

    private function assertIfSupported(bool $switchLocalizationBasedOnUrl = true)
    {
        $website = new Website();
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects($this->once())
            ->method('setScopeIdFromEntity')
            ->with($website);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::SWITCH_LOCALIZATION_BASED_ON_URL),
                Configuration::SWITCH_LOCALIZATION_BASED_ON_URL_DEFAULT_VALUE
            )->willReturn($switchLocalizationBasedOnUrl);
    }

    private function refreshSlug(Slug $usedSlug): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);
        $manager->expects($this->once())
            ->method('refresh')
            ->with($usedSlug);
    }
}
