<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\EventListener\ProductSearchIndexListener;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder as Placeholder;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductSearchIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testOnWebsiteSearchIndexNotSupportedFieldsGroup()
    {
        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']];
        $event = new IndexEntityEvent(Category::class, [$this->getEntity(Category::class, ['id' => 1])], $context);

        $localizationProvider = $this->createMock(AbstractWebsiteLocalizationProvider::class);
        $localizationProvider->expects($this->never())
            ->method($this->anything());

        $websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $websiteContextManager->expects($this->never())
            ->method($this->anything());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->never())
            ->method($this->anything());

        $testable = new ProductSearchIndexListener(
            $doctrineHelper,
            $localizationProvider,
            $websiteContextManager
        );
        $testable->onWebsiteSearchIndex($event);
    }

    /**
     * @dataProvider contextDataProvider
     * @SuppressWarnings(ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchIndex(array $context)
    {
        $localizations = [
            'PL' => $this->getLocalization(10),
            'EN' => $this->getLocalization(20)
        ];
        $products = $this->getProductEntities([1, 2, 3]);

        $localizationProvider = $this->createMock(AbstractWebsiteLocalizationProvider::class);
        $localizationProvider->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn($localizations);

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects($this->once())
            ->method('getEntities')
            ->willReturn($products);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $event->expects($this->exactly(10))
            ->method('addPlaceholderField')
            ->withConsecutive(
                [1, 'all_text_LOCALIZATION_ID', 'PL Category meta description', [Placeholder::NAME => 10]],
                [1, 'all_text_LOCALIZATION_ID', 'PL Category meta keywords', [Placeholder::NAME => 10]],
                [1, 'all_text_LOCALIZATION_ID', 'EN Category meta title', [Placeholder::NAME => 20]],
                [1, 'all_text_LOCALIZATION_ID', 'EN Category meta description', [Placeholder::NAME => 20]],
                [1, 'all_text_LOCALIZATION_ID', 'EN Category meta keywords', [Placeholder::NAME => 20]],
                [2, 'all_text_LOCALIZATION_ID', 'PL Category meta description', [Placeholder::NAME => 10]],
                [2, 'all_text_LOCALIZATION_ID', 'PL Category meta keywords', [Placeholder::NAME => 10]],
                [2, 'all_text_LOCALIZATION_ID', 'EN Category meta title', [Placeholder::NAME => 20]],
                [2, 'all_text_LOCALIZATION_ID', 'EN Category meta description', [Placeholder::NAME => 20]],
                [2, 'all_text_LOCALIZATION_ID', 'EN Category meta keywords', [Placeholder::NAME => 20]]
            );

        $category = $this->getCategory(777, $localizations);

        $repository = $this->createMock(CategoryRepository::class);
        $repository->expects($this->once())
            ->method('getCategoryMapByProducts')
            ->willReturn([
                $products[0]->getId() => $category,
                $products[1]->getId() => $category,
            ]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Category::class)
            ->willReturn($repository);

        $websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $testable = new ProductSearchIndexListener(
            $doctrineHelper,
            $localizationProvider,
            $websiteContextManager
        );
        $testable->onWebsiteSearchIndex($event);
    }

    public function contextDataProvider(): \Generator
    {
        yield [[]];
        yield [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]];
    }

    /**
     * @param array $entityIds
     *
     * @return Product[]
     */
    private function getProductEntities(array $entityIds): array
    {
        $result = [];

        foreach ($entityIds as $id) {
            $product = $this->createMock(Product::class);
            $product->expects($this->any())
                ->method('getId')
                ->willReturn($id);

            $result[] = $product;
        }

        return $result;
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    private function getCategory(int $categoryId, array $localizations): Category
    {
        $category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getMetaTitle', 'getMetaDescription', 'getMetaKeyword'])
            ->getMock();
        $category->expects($this->any())
            ->method('getId')
            ->willReturn($categoryId);
        $category->expects($this->any())
            ->method('getMetaTitle')
            ->willReturnMap([
                [$localizations['PL'], null],
                [$localizations['EN'], 'EN Category meta title'],
            ]);
        $category->expects($this->any())
            ->method('getMetaDescription')
            ->willReturnMap([
                [$localizations['PL'], 'PL Category meta description'],
                [$localizations['EN'], "\tEN Category meta\r\n description"],
            ]);
        $category->expects($this->any())
            ->method('getMetaKeyword')
            ->willReturnMap([
                [$localizations['PL'], 'PL Category meta keywords'],
                [$localizations['EN'], 'EN Category meta keywords'],
            ]);

        return $category;
    }
}
