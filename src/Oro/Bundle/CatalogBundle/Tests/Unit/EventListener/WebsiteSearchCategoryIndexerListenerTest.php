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
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteSearchCategoryIndexerListenerTest extends TestCase
{
    use EntityTrait;

    const NAME_DEFAULT_LOCALE = 'name default';
    const NAME_CUSTOM_LOCALE = 'name custom';
    const DESCRIPTION_DEFAULT_LOCALE = 'description default';
    const DESCRIPTION_CUSTOM_LOCALE = 'description custom';
    const SHORT_DESCRIPTION_DEFAULT_LOCALE = 'short description default';
    const SHORT_DESCRIPTION_CUSTOM_LOCALE = 'short description custom';

    const FIRST_PARENT_NAME_DEFAULT_LOCALE = 'first parent name default';
    const FIRST_PARENT_NAME_CUSTOM_LOCALE = 'first parent name custom';

    const SECOND_PARENT_NAME_DEFAULT_LOCALE = 'second parent name default';
    const SECOND_PARENT_NAME_CUSTOM_LOCALE = 'second parent name custom';

    /**
     * @var WebsiteSearchCategoryIndexerListener
     */
    private $listener;

    /**
     * @var DoctrineHelper|MockObject
     */
    private $doctrineHelper;

    /**
     * @var AbstractWebsiteLocalizationProvider|MockObject
     */
    private $websiteLocalizationProvider;

    /**
     * @var WebsiteContextManager|MockObject
     */
    private $websiteContextManager;

    /**
     * @var CategoryRepository|MockObject
     */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->websiteLocalizationProvider = $this->createMock(AbstractWebsiteLocalizationProvider::class);
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);

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
     * @return LocalizedFallbackValue|CategoryTitle|CategoryLongDescription|CategoryShortDescription
     */
    private function prepareLocalizedValue(
        ?Localization $localization = null,
        ?string $string = null,
        ?string $text = null,
        string $className = LocalizedFallbackValue::class
    ): object {
        $value = new $className();
        $value->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    private function prepareCategory(
        ?Localization $defaultLocale,
        ?Localization $customLocale,
        Product $product,
        Category $parentCategory
    ): Category {
        $category = $this->getEntity(
            Category::class,
            [
                'id' => 555,
                'materializedPath' => '1_33_555'
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
            ->addProduct($product)
            ->setParentCategory($parentCategory);

        return $category;
    }

    private function prepareFirstParentCategory(
        ?Localization $defaultLocale,
        ?Localization $customLocale,
        Category $parentCategory
    ): Category {
        $category = $this->getEntity(
            Category::class,
            [
                'id' => 33,
                'materializedPath' => '1_33'
            ]
        );

        $category
            ->addTitle(
                $this->prepareLocalizedValue(
                    $defaultLocale,
                    self::FIRST_PARENT_NAME_DEFAULT_LOCALE,
                    null,
                    CategoryTitle::class
                )
            )
            ->addTitle(
                $this->prepareLocalizedValue(
                    $customLocale,
                    self::FIRST_PARENT_NAME_CUSTOM_LOCALE,
                    null,
                    CategoryTitle::class
                )
            )
            ->setParentCategory($parentCategory);

        return $category;
    }

    private function prepareSecondParentCategory(
        ?Localization $defaultLocale,
        ?Localization $customLocale
    ): Category {
        $category = $this->getEntity(
            Category::class,
            [
                'id' => 1,
                'materializedPath' => '1'
            ]
        );

        $category
            ->addTitle(
                $this->prepareLocalizedValue(
                    $defaultLocale,
                    self::SECOND_PARENT_NAME_DEFAULT_LOCALE,
                    null,
                    CategoryTitle::class
                )
            )
            ->addTitle(
                $this->prepareLocalizedValue(
                    $customLocale,
                    self::SECOND_PARENT_NAME_CUSTOM_LOCALE,
                    null,
                    CategoryTitle::class
                )
            );

        return $category;
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchIndexProductClass(?array $fieldsGroup)
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
        $product = $this->getEntity(Product::class, ['id' => 1, 'category_sort_order' => 1]);
        $secondParentCategory = $this->prepareSecondParentCategory($defaultValueLocale, $customLocale);
        $firstParentCategory =
            $this->prepareFirstParentCategory($defaultValueLocale, $customLocale, $secondParentCategory);
        $category = $this->prepareCategory($defaultValueLocale, $customLocale, $product, $firstParentCategory);
        $this->repository
            ->expects($this->once())
            ->method('getCategoryMapByProducts')
            ->willReturn([$product->getId() => $category]);
        $this->repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1 => '33', 2 => '555']])
            ->willReturn([$category, $firstParentCategory]);

        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => $fieldsGroup];
        $event = new IndexEntityEvent(Product::class, [$product], $context);

        $this->websiteContextManager
            ->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()] = [
            'category_id' => [
                ['value' => 555, 'all_text' => false],
            ],
            'category_path' => [
                ['value' => '1_33_555', 'all_text' => false],
            ],
            'category_paths.' . CategoryPathPlaceholder::NAME => [
                [
                    'value' => new PlaceholderValue(1, [CategoryPathPlaceholder::NAME => '1']),
                    'all_text' => false,
                ],
                [
                    'value' => new PlaceholderValue(1, [CategoryPathPlaceholder::NAME => '1_33']),
                    'all_text' => false,
                ],
                [
                    'value' => new PlaceholderValue(1, [CategoryPathPlaceholder::NAME => '1_33_555']),
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
            'category_id_with_parent_categories_LOCALIZATION_ID'  => [
                [
                    'value' => new PlaceholderValue(
                        sprintf('555|%s|%s', self::FIRST_PARENT_NAME_CUSTOM_LOCALE, self::NAME_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                    'all_text' => false,
                ]
            ],
        ];
        if (is_null($context[AbstractIndexer::CONTEXT_FIELD_GROUPS])) {
            $expected[$product->getId()]['category_sort_order'] = [
                [
                    'value' => 1, 'all_text' => false
                ]
            ];
        }

        $this->assertEquals($expected, $event->getEntitiesData());
    }

    public function fieldsGroupDataProvider(): \Generator
    {
        yield [null];
        yield [['main']];
    }

    public function testOnWebsiteSearchCategorySortOrderFieldGroup()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1, 'category_sort_order' => 1]);

        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['category_sort_order']];
        $event = new IndexEntityEvent(Product::class, [$product], $context);

        $this->websiteContextManager
            ->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(1);

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()]['category_sort_order'] = [
            [
                'value' => 1, 'all_text' => false
            ]
        ];

        $this->assertEquals($expected, $event->getEntitiesData());
    }

    public function testOnWebsiteSearchIndexUnsupportedFieldsGroup()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']];
        $event = new IndexEntityEvent(Product::class, [$product], $context);

        $this->websiteLocalizationProvider->expects($this->never())
            ->method($this->anything());

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->websiteContextManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onWebsiteSearchIndex($event);
    }
}
