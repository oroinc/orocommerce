<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Extension;

use Oro\Bundle\FrontendLocalizationBundle\Extension\RequestCurrentLocalizationExtension;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestCurrentLocalizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack */
    private $requestStack;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var RequestCurrentLocalizationExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->extension = new RequestCurrentLocalizationExtension(
            $this->requestStack,
            $this->localizationManager
        );
    }

    public function testGetCurrentLocalizationWithoutLocaleHeader()
    {
        $this->assertSame(null, $this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithEmptyLocalizationHeader()
    {
        $request = new Request();
        $request->headers->set('X-Localization-ID', '');
        $this->requestStack->push($request);

        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithNotIntegerLocalizationHeader()
    {
        $request = new Request();
        $request->headers->set('X-Localization-ID', 'test');
        $this->requestStack->push($request);

        $this->localizationManager->expects(self::never())
            ->method('getLocalization');

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithUnknownValueInLocalizationHeader()
    {
        $request = new Request();
        $request->headers->set('X-Localization-ID', '123');
        $this->requestStack->push($request);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::identicalTo(123))
            ->willReturn(null);

        $this->assertNull($this->extension->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWithLocalizationHeader()
    {
        $localization = $this->createMock(Localization::class);

        $request = new Request();
        $request->headers->set('X-Localization-ID', '123');
        $this->requestStack->push($request);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::identicalTo(123))
            ->willReturn($localization);

        $this->assertSame($localization, $this->extension->getCurrentLocalization());
    }
}
