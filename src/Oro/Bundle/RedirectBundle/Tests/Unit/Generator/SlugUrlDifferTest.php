<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class SlugUrlDifferTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var SlugUrlDiffer */
    private $differ;

    protected function setUp(): void
    {
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->differ = new SlugUrlDiffer($this->localizationHelper, $this->translator);
    }

    public function testGetUrlsChangesWhenUrlsEqual()
    {
        $englishTitles = new ArrayCollection([
            $this->getEntity(LocalizedFallbackValue::class, ['id' => 1, 'string' => 'English label'])
        ]);

        $frenchTitles = new ArrayCollection([
            $this->getEntity(LocalizedFallbackValue::class, ['id' => 2, 'string' => 'French label'])
        ]);

        $englishLocalization = $this->getEntity(Localization::class, ['id' => 1, 'titles' => $englishTitles]);

        $frenchLocalization = $this->getEntity(Localization::class, ['id' => 2, 'titles' => $frenchTitles]);

        $urlsBefore = new ArrayCollection([
            new SlugUrl('/default/url'),
            new SlugUrl('/en/url', $englishLocalization),
            new SlugUrl('/french/url', $frenchLocalization),
        ]);

        $urlsAfter = new ArrayCollection([
            new SlugUrl('/default/url'),
            new SlugUrl('/en/url', $englishLocalization),
            new SlugUrl('/french/url', $frenchLocalization),
        ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.locale.fallback.type.default')
            ->willReturn('Default label');

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnMap([
                [$englishTitles, null, 'English label'],
                [$frenchTitles, null, 'French label'],
            ]);

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizations')
            ->willReturn([$englishLocalization, $frenchLocalization]);

        $this->assertEmpty($this->differ->getSlugUrlsChanges($urlsBefore, $urlsAfter));
    }

    public function testGetUrlsChangesWhenUrlsDiffer()
    {
        $englishTitles = new ArrayCollection([
            $this->getEntity(LocalizedFallbackValue::class, ['id' => 1, 'string' => 'English label'])
        ]);

        $frenchTitles = new ArrayCollection([
            $this->getEntity(LocalizedFallbackValue::class, ['id' => 2, 'string' => 'French label'])
        ]);

        $englishLocalization = $this->getEntity(Localization::class, ['id' => 1, 'titles' => $englishTitles]);

        $frenchLocalization = $this->getEntity(Localization::class, ['id' => 2, 'titles' => $frenchTitles]);

        $urlsBefore = new ArrayCollection([
            new SlugUrl('/default/url'),
            new SlugUrl('/en/url', $englishLocalization),
            new SlugUrl('/french/url', $frenchLocalization),
        ]);

        $urlsAfter = new ArrayCollection([
            new SlugUrl('/default/urlnew'),
            new SlugUrl('/en/url', $englishLocalization),
            new SlugUrl('/french/urlnew', $frenchLocalization),
        ]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.locale.fallback.type.default')
            ->willReturn('Default label');

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(function ($titles) {
                return $titles[0]->getString();
            });

        $this->localizationHelper->expects($this->any())
            ->method('getLocalizations')
            ->willReturn([$englishLocalization, $frenchLocalization]);

        $expectedChanges = [
            'Default label' => [
                'before' => '/default/url',
                'after' => '/default/urlnew'
            ],
            'French label' => [
                'before' => '/french/url',
                'after' => '/french/urlnew'
            ]
        ];

        $this->assertEquals($expectedChanges, $this->differ->getSlugUrlsChanges($urlsBefore, $urlsAfter));
    }
}
