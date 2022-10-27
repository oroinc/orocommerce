<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Provider\LandingPageProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class LandingPageProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CMS_PAGE_TYPE = 'cms';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var LandingPageProvider */
    private $provider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->provider = new LandingPageProvider($this->doctrine, $this->localizationHelper);
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

            $this->doctrine->expects($this->once())
                ->method('getRepository')
                ->willReturn($this->repository);
            $this->localizationHelper->expects($this->any())
                ->method('getLocalizedValue')
                ->willReturnCallback(function (Collection $collection) {
                    return $collection->first()->getString();
                });
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
                'expected' => ''
            ],
            'variants without cms pages' => [
                'actualIds' => '12, 35, 40',
                'foundVariants' => [
                    $this->createVariant('', 'product_collection'),
                    $this->createVariant('', 'product_collection')
                ],
                'expected' => ''
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
