<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\EventListener\ProductSearchIndexListener;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class ProductSearchIndexListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnWebsiteSearchIndex()
    {
        $entityIds     = [1, 2];
        $localizations = $this->getLocalizations();
        $entities      = $this->getProductEntities($entityIds, $localizations);

        $event = $this->getMockBuilder(IndexEntityEvent::class)
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->at(0))
            ->method('getEntities')
            ->willReturn($entities);

        $event->expects($this->at(1))
            ->method('getContext')
            ->willReturn([]);

        $localizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $localizationProvider->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn($localizations);

        $event->expects($this->exactly(4))
            ->method('addPlaceholderField')
            ->withConsecutive(
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'Polish metaTitle Polish meta description Polish meta keywords',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    1,
                    'all_text_LOCALIZATION_ID',
                    'English metaTitle English meta description English meta keywords',
                    [LocalizationIdPlaceholder::NAME => 2],
                ],
                [
                    2,
                    'all_text_LOCALIZATION_ID',
                    'Polish metaTitle Polish meta description Polish meta keywords',
                    [LocalizationIdPlaceholder::NAME => 1],
                ],
                [
                    2,
                    'all_text_LOCALIZATION_ID',
                    'English metaTitle English meta description English meta keywords',
                    [LocalizationIdPlaceholder::NAME => 2],
                ]
            );

        $propertyAccessor = new PropertyAccessor();
        $testable         = new ProductSearchIndexListener($localizationProvider, $propertyAccessor);
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
            $product = $this->getMock(
                Product::class,
                ['getId', 'getMetaTitle', 'getMetaDescription', 'getMetaKeyword']
            );

            $product->expects($this->at(0))
                ->method('getMetaTitle')
                ->willReturn("Polish\n metaTitle");

            $product->expects($this->at(1))
                ->method('getMetaDescription')
                ->willReturn('Polish meta description');

            $product->expects($this->at(2))
                ->method('getMetaKeyword')
                ->willReturn('Polish meta keywords');

            $product->expects($this->at(3))
                ->method('getId')
                ->willReturn($id);

            $product->expects($this->at(4))
                ->method('getMetaTitle')
                ->willReturn('English metaTitle');

            $product->expects($this->at(5))
                ->method('getMetaDescription')
                ->willReturn("\tEnglish meta\r\n description");

            $product->expects($this->at(6))
                ->method('getMetaKeyword')
                ->willReturn('English meta keywords');

            $product->expects($this->at(7))
                ->method('getId')
                ->willReturn($id);

            $result[] = $product;
        }

        return $result;
    }

    /**
     * @return Localization[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    private function getLocalizations()
    {
        $polishLocalization = $this->getMock(
            Localization::class,
            ['getId']
        );

        $polishLocalization->expects($this->atLeast(1))->method('getId')
            ->willReturn(1);

        $englishLocalization = $this->getMock(
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
