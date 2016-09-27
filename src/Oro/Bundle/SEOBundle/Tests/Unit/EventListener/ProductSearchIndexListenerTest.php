<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SEOBundle\EventListener\ProductSearchIndexListener;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Component\PropertyAccess\PropertyAccessor;

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
            ->method('getEntityClass')
            ->willReturn(Product::class);

        $event->expects($this->at(1))
            ->method('getEntityIds')
            ->willReturn($entityIds);

        $productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()->getMock();

        $productRepository->expects($this->once())
            ->method('getProductsByIds')
            ->with($entityIds)
            ->willReturn($entities);

        $localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()->getMock();

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();

        $localizationHelper->expects($this->once())
            ->method('getLocalizations')
            ->willReturn($localizations);

        $doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($productRepository);

        $event->expects($this->at(2))
            ->method('appendField')
            ->with(
                1,
                Query::TYPE_TEXT,
                'all_text_1',
                ' Polish metaTitle Polish meta description Polish meta keywords'
            );

        $event->expects($this->at(3))
            ->method('appendField')
            ->with(
                1,
                Query::TYPE_TEXT,
                'all_text_2',
                ' English metaTitle English meta description English meta keywords'
            );

        $event->expects($this->at(4))
            ->method('appendField')
            ->with(
                2,
                Query::TYPE_TEXT,
                'all_text_1',
                ' Polish metaTitle Polish meta description Polish meta keywords'
            );

        $event->expects($this->at(5))
            ->method('appendField')
            ->with(
                2,
                Query::TYPE_TEXT,
                'all_text_2',
                ' English metaTitle English meta description English meta keywords'
            );

        $propertyAccessor = new PropertyAccessor();
        $testable = new ProductSearchIndexListener($doctrineHelper, $localizationHelper, $propertyAccessor);
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
