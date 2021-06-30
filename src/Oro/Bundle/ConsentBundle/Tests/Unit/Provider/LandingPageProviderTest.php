<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Provider\LandingPageProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class LandingPageProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CMS_PAGE_TYPE = 'cms';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var LandingPageProvider */
    private $provider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider = new LandingPageProvider($this->doctrine, $this->localizationHelper, $this->translator);
    }

    /**
     * @dataProvider variantsProvider
     */
    public function testGetLandingPage(string $actualIds, ?array $variants, string $expected)
    {
        if ($actualIds) {
            $this->repository->expects($this->once())
                ->method('findBy')
                ->willReturn($variants);

            $objectManager = $this->createMock(ObjectManager::class);
            $objectManager->expects($this->once())
                ->method('getRepository')
                ->willReturn($this->repository);
            $this->doctrine->expects($this->once())
                ->method('getManagerForClass')
                ->willReturn($objectManager);
            $this->localizationHelper->expects($this->any())
                ->method('getLocalizedValue')
                ->willReturnCallback(function (Collection $collection) {
                    return $collection->first()->getString();
                });
            $this->translator->expects($this->any())
                ->method('trans')
                ->with('oro.consent.content_source.none')
                ->willReturn('N/A');
        } else {
            $this->translator->expects($this->once())
                ->method('trans')
                ->with('oro.consent.content_source.none')
                ->willReturn('N/A');
        }

        $result = $this->provider->getLandingPages($actualIds);
        $this->assertEquals($expected, $result);
    }

    public function variantsProvider(): array
    {
        return [
            'string with ids' => [
                'actualIds' => '12,35,40',
                'foundVariants' => [
                    $this->createVariant('Test Page 1'),
                    $this->createVariant('', 'product_collection'),
                    $this->createVariant('Test Page 2')
                ],
                'expected' => 'Test Page 1, Test Page 2'
            ],
            'empty string' => [
                'actualIds' => '',
                'foundVariants' => null,
                'expected' => 'N/A'
            ],
            'variants without cms pages' => [
                'actualIds' => '12, 35, 40',
                'foundVariants' => [
                    $this->createVariant('', 'product_collection'),
                    $this->createVariant('', 'product_collection')
                ],
                'expected' => 'N/A'
            ]
        ];
    }

    private function createVariant(string $title = '', string $type = self::CMS_PAGE_TYPE): ContentVariant
    {
        $variant = $this->getMockBuilder(ContentVariant::class)
            ->addMethods(['getCmsPage'])
            ->disableOriginalConstructor()
            ->getMock();
        $page = null;
        if ($type === self::CMS_PAGE_TYPE) {
            $page = $this->createMock(Page::class);
            $titlesCollection = $this->createMock(Collection::class);
            $localizedValue = new LocalizedFallbackValue();
            $localizedValue->setString($title);
            $titlesCollection->expects($this->any())
                ->method('first')
                ->willReturn($localizedValue);
            $page->expects($this->any())
                ->method('getTitles')
                ->willReturn($titlesCollection);
        }

        $variant->expects($this->any())
            ->method('getCmsPage')
            ->willReturn($page);

        return $variant;
    }
}
