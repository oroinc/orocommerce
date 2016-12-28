<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\EventListener\ProductSearchIndexListener;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class ProductSearchIndexListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnWebsiteSearchIndex()
    {
        $entityIds     = [1, 2];
        $localizations = $this->getLocalizations();
        $entities      = $this->getProductEntities($entityIds, $localizations);

        $event = $this->getMockBuilder(IndexEntityEvent::class)
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->once())
            ->method('getEntities')
            ->willReturn($entities);

        $event->expects($this->once())
            ->method('getContext')
            ->willReturn([]);

        $localizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $localizationProvider->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn($localizations);

        $event->expects($this->exactly(8))
            ->method('addPlaceholderField')
            ->withConsecutive(
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'Polish meta description',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'Polish meta keywords',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'English meta description',
                    [LocalizationIdPlaceholder::NAME => 2],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'English meta keywords',
                    [LocalizationIdPlaceholder::NAME => 2],
                ],
                [
                    2,
                    'all_text_LOCALIZATION_ID',
                    'Polish meta description',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    2,
                    'all_text_LOCALIZATION_ID',
                    'Polish meta keywords',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    2,
                    'all_text_LOCALIZATION_ID',
                    'English meta description',
                    [LocalizationIdPlaceholder::NAME => 2],
                ],
                [
                    2,
                    'all_text_LOCALIZATION_ID',
                    'English meta keywords',
                    [LocalizationIdPlaceholder::NAME => 2],
                ]
            );

        /** @var WebsiteContextManager|\PHPUnit_Framework_MockObject_MockObject $websiteContextManager */
        $websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteContextManager->expects($this->once())->method('getWebsiteId')->with([])->willReturn(1);

        $testable = new ProductSearchIndexListener(
            $localizationProvider,
            $websiteContextManager
        );
        $testable->onWebsiteSearchIndex($event);
    }

    /**
     * @param array          $entityIds
     * @param Localization[] $localizations
     * @return \Oro\Bundle\ProductBundle\Entity\Product[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    private function getProductEntities($entityIds, $localizations)
    {
        $result = [];

        foreach ($entityIds as $id) {
            $product = $this->getMockBuilder(Product::class)
                ->disableOriginalConstructor()
                ->setMethods(['getId', 'getMetaDescription', 'getMetaKeyword'])
                ->getMock();

            $product->expects($this->any())
                ->method('getId')
                ->willReturn($id);

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
     * @return Localization[]|\PHPUnit_Framework_MockObject_MockObject[]
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
}
