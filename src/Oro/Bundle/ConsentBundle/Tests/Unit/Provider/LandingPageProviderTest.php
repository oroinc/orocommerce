<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Provider\LandingPageProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class LandingPageProviderTest extends \PHPUnit\Framework\TestCase
{
    const CMS_PAGE_TYPE = 'cms';
    const CMS_PAGE_NAME = 'Test Page';

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrine;

    /**
     * @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var LandingPageProvider
     */
    private $provider;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localizationHelper;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider = new LandingPageProvider($this->doctrine, $this->localizationHelper, $this->translator);
    }

    /**
     * @param string $actualIds
     * @param array $variants
     * @param string $expected
     * @dataProvider variantsProvider
     */
    public function testGetLandingPage(string $actualIds, $variants, string $expected)
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
                ->will($this->returnCallback(function (Collection $collection) {
                    return $collection->first()->getString();
                }));
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

    /**
     * @return \Generator
     */
    public function variantsProvider()
    {
        yield 'string with ids' => [
            'actualIds' => '12,35,40',
            'foundVariants' => [
                $this->createVariant('Test Page 1'),
                $this->createVariant('', 'product_collection'),
                $this->createVariant('Test Page 2')
            ],
            'expected' => 'Test Page 1, Test Page 2'
        ];

        yield 'empty string' => [
            'actualIds' => '',
            'foundVariants' => null,
            'expected' => 'N/A'
        ];

        yield 'variants without cms pages' => [
            'actualIds' => '12, 35, 40',
            'foundVariants' => [
                $this->createVariant('', 'product_collection'),
                $this->createVariant('', 'product_collection')
            ],
            'expected' => 'N/A'
        ];
    }

    /**
     * @param string $title
     * @param string $type
     *
     * @return ContentVariant|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createVariant($title = '', $type = self::CMS_PAGE_TYPE)
    {
        $variant =  $this->getMockBuilder(ContentVariant::class)
            ->setMethods(['getCmsPage'])
            ->disableOriginalConstructor()
            ->getMock();
        $page = null;
        if ($type == self::CMS_PAGE_TYPE) {
            $page = $this->getMockBuilder(Page::class)
                ->setMethods(['getTitles'])
                ->disableOriginalConstructor()
                ->getMock();
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
