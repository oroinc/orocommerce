<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Category as BaseCategory;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\EventListener\ProductSearchIndexListener;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class ProductSearchIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @SuppressWarnings(ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchIndex()
    {
        $entityIds     = [1, 2];
        $localizations = $this->getLocalizations();
        $entities      = $this->getProductEntities($entityIds, $localizations);
        /** @var IndexEntityEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(IndexEntityEvent::class)
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())
            ->method('getEntities')
            ->willReturn($entities);

        $event->expects($this->once())
            ->method('getContext')
            ->willReturn([]);
        /** @var AbstractWebsiteLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject $localizationProvider */
        $localizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $localizationProvider->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn($localizations);

        $event->expects($this->exactly(12))
            ->method('addPlaceholderField')
            ->withConsecutive(
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'Polish Category meta title',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'Polish Category meta description',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'Polish Category meta keywords',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'English Category meta title',
                    [LocalizationIdPlaceholder::NAME => 2],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'English Category meta description',
                    [LocalizationIdPlaceholder::NAME => 2],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'English Category meta keywords',
                    [LocalizationIdPlaceholder::NAME => 2],
                ]
            );

        /** @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject $websiteContextManager */
        $websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $category = $this->prepareCategory(777, $localizations);
        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository
            ->expects($this->once())
            ->method('getCategoryMapByProducts')
            ->willReturn([
                $entities[0]->getId() => $category,
                $entities[1]->getId() => $category,
            ]);
        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(BaseCategory::class)
            ->willReturn($repository);

        $websiteContextManager->expects($this->once())->method('getWebsiteId')->with([])->willReturn(1);

        $testable = new ProductSearchIndexListener(
            $doctrineHelper,
            $localizationProvider,
            $websiteContextManager
        );
        $testable->onWebsiteSearchIndex($event);
    }

    /**
     * @param array          $entityIds
     * @param Localization[] $localizations
     * @return \Oro\Bundle\ProductBundle\Entity\Product[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function getProductEntities($entityIds, $localizations)
    {
        $result = [];

        foreach ($entityIds as $id) {
            $product = $this->getMockBuilder(Product::class)
                ->disableOriginalConstructor()
                ->setMethods(['getId', 'getMetaTitle', 'getMetaDescription', 'getMetaKeyword'])
                ->getMock();

            $product->expects($this->any())
                ->method('getId')
                ->willReturn($id);

            $product->expects($this->any())
                ->method('getMetaTitle')
                ->willReturnMap(
                    [
                        [$localizations['PL'], 'Polish meta title'],
                        [$localizations['EN'], "English meta title"],
                    ]
                );

            $product->expects($this->any())
                ->method('getMetaDescription')
                ->willReturnMap(
                    [
                        [$localizations['PL'], 'Polish meta description'],
                        [$localizations['EN'], "\tEnglish meta\r\n description"],
                    ]
                );
            $product->expects($this->any())
                ->method('getMetaKeyword')
                ->willReturnMap(
                    [
                        [$localizations['PL'], 'Polish meta keywords'],
                        [$localizations['EN'], 'English meta keywords'],
                    ]
                );

            $result[] = $product;
        }

        return $result;
    }

    /**
     * @return Localization[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function getLocalizations()
    {
        $polishLocalization = $this->createMock(
            Localization::class,
            ['getId']
        );

        $polishLocalization->expects($this->atLeast(1))->method('getId')
            ->willReturn(1);

        $englishLocalization = $this->createMock(
            Localization::class,
            ['getId']
        );

        $englishLocalization->expects($this->atLeast(1))->method('getId')
            ->willReturn(2);

        return [
            'PL' => $polishLocalization,
            'EN' => $englishLocalization
        ];
    }

    /**
     * @param integer $categoryId
     * @param array $localizations
     * @return Category|\PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareCategory($categoryId, $localizations)
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getMetaTitle', 'getMetaDescription', 'getMetaKeyword'])
            ->getMock();

        $category->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);

        $category->expects($this->any())
            ->method('getMetaTitle')
            ->willReturnMap(
                [
                    [$localizations['PL'], 'Polish Category meta title'],
                    [$localizations['EN'], "English Category meta title"],
                ]
            );

        $category->expects($this->any())
            ->method('getMetaDescription')
            ->willReturnMap(
                [
                    [$localizations['PL'], 'Polish Category meta description'],
                    [$localizations['EN'], "\tEnglish Category meta\r\n description"],
                ]
            );
        $category->expects($this->any())
            ->method('getMetaKeyword')
            ->willReturnMap(
                [
                    [$localizations['PL'], 'Polish Category meta keywords'],
                    [$localizations['EN'], 'English Category meta keywords'],
                ]
            );
        return $category;
    }
}
