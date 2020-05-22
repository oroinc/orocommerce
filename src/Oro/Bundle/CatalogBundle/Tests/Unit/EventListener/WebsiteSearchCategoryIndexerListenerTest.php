<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category as BaseCategory;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchCategoryIndexerListener;
use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchCategoryIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    const NAME_DEFAULT_LOCALE = 'name default';
    const NAME_CUSTOM_LOCALE = 'name custom';
    const DESCRIPTION_DEFAULT_LOCALE = 'description default';
    const DESCRIPTION_CUSTOM_LOCALE = 'description custom';
    const SHORT_DESCRIPTION_DEFAULT_LOCALE = 'short description default';
    const SHORT_DESCRIPTION_CUSTOM_LOCALE = 'short description custom';

    /**
     * @var WebsiteSearchCategoryIndexerListener
     */
    private $listener;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var AbstractWebsiteLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteLocalizationProvider;

    /**
     * @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteContextManager;

    /**
     * @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteLocalizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchCategoryIndexerListener(
            $this->doctrineHelper,
            $this->websiteLocalizationProvider,
            $this->websiteContextManager
        );
    }

    /**
     * @param Localization|null $localization
     * @param string|null $string
     * @param string|null $text
     * @param string $className
     * @return LocalizedFallbackValue
     */
    private function prepareLocalizedValue(
        ?Localization $localization = null,
        ?string $string = null,
        ?string $text = null,
        string $className = LocalizedFallbackValue::class
    ) {
        $value = new $className();
        $value->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    /**
     * @param Localization $defaultLocale
     * @param Localization $customLocale
     * @param Product $product
     * @return array
     */
    private function prepareCategory($defaultLocale, $customLocale, Product $product)
    {
        $category = $this->getEntity(
            Category::class,
            [
                'id' => 555,
                'materializedPath' => '1_555'
            ]
        );

        $category
            ->addTitle(
                $this->prepareLocalizedValue($defaultLocale, self::NAME_DEFAULT_LOCALE, null, CategoryTitle::class)
            )
            ->addTitle(
                $this->prepareLocalizedValue($customLocale, self::NAME_CUSTOM_LOCALE, null, CategoryTitle::class)
            )
            ->addLongDescription(
                $this->prepareLocalizedValue(
                    $defaultLocale,
                    null,
                    self::DESCRIPTION_DEFAULT_LOCALE,
                    CategoryLongDescription::class
                )
            )
            ->addLongDescription(
                $this->prepareLocalizedValue(
                    $customLocale,
                    null,
                    self::DESCRIPTION_CUSTOM_LOCALE,
                    CategoryLongDescription::class
                )
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    $defaultLocale,
                    null,
                    self::SHORT_DESCRIPTION_DEFAULT_LOCALE,
                    CategoryShortDescription::class
                )
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    $customLocale,
                    null,
                    self::SHORT_DESCRIPTION_CUSTOM_LOCALE,
                    CategoryShortDescription::class
                )
            )
            ->addProduct($product);

        return $category;
    }

    public function testOnWebsiteSearchIndexProductClass()
    {
        $defaultValueLocale = null;

        /** @var Localization $customLocale */
        $customLocale = $this->getEntity(Localization::class, ['id' => 2]);

        $this->websiteLocalizationProvider
            ->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn([$customLocale]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(BaseCategory::class)
            ->willReturn($this->repository);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $category = $this->prepareCategory($defaultValueLocale, $customLocale, $product);
        $this->repository
            ->expects($this->once())
            ->method('getCategoryMapByProducts')
            ->willReturn([$product->getId() => $category]);

        $event = new IndexEntityEvent(Product::class, [$product], ['some_context']);

        $this->websiteContextManager
            ->expects($this->once())
            ->method('getWebsiteId')
            ->with(['some_context'])
            ->willReturn(1);

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()] = [
            'category_id' => [
                ['value' => 555, 'all_text' => false],
             ],
            'category_path' => [
                ['value' => '1_555', 'all_text' => false],
             ],
            'category_path_' . CategoryPathPlaceholder::NAME => [
                [
                    'value' => new PlaceholderValue(1, [CategoryPathPlaceholder::NAME => '1']),
                    'all_text' => false,
                ],
                [
                    'value' => new PlaceholderValue(1, [CategoryPathPlaceholder::NAME => '1_555']),
                    'all_text' => false,
                ],
            ],
            'all_text_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue(
                        $this->prepareLocalizedValue($customLocale, null, self::DESCRIPTION_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                    'all_text' => true,
                ],
                [
                    'value' => new PlaceholderValue(
                        $this->prepareLocalizedValue($customLocale, null, self::SHORT_DESCRIPTION_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                    'all_text' => true,
                ],
            ],
            'category_title_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue(
                        $this->prepareLocalizedValue($customLocale, self::NAME_CUSTOM_LOCALE, null),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                    'all_text' => true,
                ]
            ],
        ];

        $this->assertEquals($expected, $event->getEntitiesData());
    }
}
