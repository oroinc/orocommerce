<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category as BaseCategory;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\WebsiteSearchCategoryIndexerListener;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ValueWithPlaceholders;
use Oro\Bundle\WebsiteSearchBundle\Provider\IndexDataProvider;
use Oro\Component\Testing\Unit\EntityTrait;

use Zend\Code\Reflection\PropertyReflection;

class WebsiteSearchCategoryIndexerListenerTest extends \PHPUnit_Framework_TestCase
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
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var AbstractWebsiteLocalizationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteLocalizationProvider;

    /**
     * @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(BaseCategory::class)
            ->willReturn($this->repository);

        $this->websiteLocalizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchCategoryIndexerListener(
            $this->doctrineHelper,
            $this->websiteLocalizationProvider
        );
    }

    /**
     * @param Localization $localization
     * @param string|null $string
     * @param string|null $text
     * @return LocalizedFallbackValue
     */
    private function prepareLocalizedValue($localization, $string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    /**
     * @param Localization $defaultLocale
     * @param Localization $customLocale
     * @return array
     */
    private function prepareProductAndCategory($defaultLocale, $customLocale)
    {
        $product = new Product();
        $category = new Category();
        $reflection = new PropertyReflection(Category::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($category, 555);
        $category->setMaterializedPath('1_555');

        $category->addTitle($this->prepareLocalizedValue($defaultLocale, self::NAME_DEFAULT_LOCALE, null))
            ->addTitle($this->prepareLocalizedValue($customLocale, self::NAME_CUSTOM_LOCALE, null))
            ->addLongDescription($this->prepareLocalizedValue($defaultLocale, null, self::DESCRIPTION_DEFAULT_LOCALE))
            ->addLongDescription($this->prepareLocalizedValue($customLocale, null, self::DESCRIPTION_CUSTOM_LOCALE))
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    $defaultLocale,
                    null,
                    self::SHORT_DESCRIPTION_DEFAULT_LOCALE
                )
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    $customLocale,
                    null,
                    self::SHORT_DESCRIPTION_CUSTOM_LOCALE
                )
            )
            ->addProduct($product);

        return [$product, $category];
    }

    public function testOnWebsiteSearchIndexProductClass()
    {
        /** @var Localization $defaultLocale */
        $defaultLocale = $this->getEntity(Localization::class, ['id' => 1]);

        /** @var Localization $customLocale */
        $customLocale = $this->getEntity(Localization::class, ['id' => 2]);

        $this->websiteLocalizationProvider
            ->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn([
                $defaultLocale,
                $customLocale
            ]);

        /**
         * @var Product $product
         * @var Category $category
         */
        list($product, $category) = $this->prepareProductAndCategory($defaultLocale, $customLocale);
        $this->repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->willReturn($category);

        $event = new IndexEntityEvent(Product::class, [$product], []);

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()] = [
            IndexDataProvider::STANDARD_VALUES_KEY => [
                'category_id' => 555,
                'category_path' => '1_555',
            ],
            IndexDataProvider::PLACEHOLDER_VALUES_KEY => [
                'category_title' => [
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($defaultLocale, self::NAME_DEFAULT_LOCALE, null),
                        [LocalizationIdPlaceholder::NAME => $defaultLocale->getId()]
                    ),
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($customLocale, self::NAME_CUSTOM_LOCALE, null),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                ],
                'category_description' => [
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($defaultLocale, null, self::DESCRIPTION_DEFAULT_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $defaultLocale->getId()]
                    ),
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($customLocale, null, self::DESCRIPTION_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                ],
                'category_short_desc' => [
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($defaultLocale, null, self::SHORT_DESCRIPTION_DEFAULT_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $defaultLocale->getId()]
                    ),
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($customLocale, null, self::SHORT_DESCRIPTION_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                ],
            ]
        ];

        $this->assertEquals($expected, $event->getEntitiesData());
    }

    public function testOnWebsiteSearchIndexNotSupportedClass()
    {
        $event = new IndexEntityEvent(\stdClass::class, [new Product()], []);
        $this->listener->onWebsiteSearchIndex($event);
        $this->assertEquals([], $event->getEntitiesData());
    }
}
