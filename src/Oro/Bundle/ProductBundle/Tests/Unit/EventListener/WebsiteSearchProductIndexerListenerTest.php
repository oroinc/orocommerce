<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ValueWithPlaceholders;
use Oro\Bundle\WebsiteSearchBundle\Provider\IndexDataProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const NAME_DEFAULT_LOCALE = 'name default';
    const NAME_CUSTOM_LOCALE = 'name custom';
    const DESCRIPTION_DEFAULT_LOCALE = 'description default';
    const DESCRIPTION_CUSTOM_LOCALE = 'description custom';
    const SHORT_DESCRIPTION_DEFAULT_LOCALE = 'short description default';
    const SHORT_DESCRIPTION_CUSTOM_LOCALE = 'short description custom';

    /**
     * @var WebsiteSearchProductIndexerListener
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

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteLocalizationProvider = $this->getMockBuilder(AbstractWebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductIndexerListener(
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
     * @return Product
     */
    private function prepareProduct($defaultLocale, $customLocale)
    {
        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryStatus->expects($this->once())->method('getId')->willReturn(Product::INVENTORY_STATUS_IN_STOCK);

        $product = $this->getEntity(Product::class, [
            'id' => 777,
            'sku' => 'sku123',
            'status' => Product::STATUS_ENABLED,
            'inventoryStatus' => $inventoryStatus
        ]);

        $product->addName($this->prepareLocalizedValue($defaultLocale, self::NAME_DEFAULT_LOCALE, null))
            ->addName($this->prepareLocalizedValue($customLocale, self::NAME_CUSTOM_LOCALE, null))
            ->addDescription($this->prepareLocalizedValue($defaultLocale, null, self::DESCRIPTION_DEFAULT_LOCALE))
            ->addDescription($this->prepareLocalizedValue($customLocale, null, self::DESCRIPTION_CUSTOM_LOCALE))
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
            );

        return $product;
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

        $product = $this->prepareProduct($defaultLocale, $customLocale);

        $event = new IndexEntityEvent([$product], []);

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()] = [
            IndexDataProvider::STANDARD_VALUES_KEY => [
                'sku' => 'sku123',
                'status' => Product::STATUS_ENABLED,
                'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK
            ],
            IndexDataProvider::PLACEHOLDER_VALUES_KEY => [
                'title' => [
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($defaultLocale, self::NAME_DEFAULT_LOCALE, null),
                        [LocalizationIdPlaceholder::NAME => $defaultLocale->getId()]
                    ),
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($customLocale, self::NAME_CUSTOM_LOCALE, null),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                ],
                'description' => [
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($defaultLocale, null, self::DESCRIPTION_DEFAULT_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $defaultLocale->getId()]
                    ),
                    new ValueWithPlaceholders(
                        $this->prepareLocalizedValue($customLocale, null, self::DESCRIPTION_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $customLocale->getId()]
                    ),
                ],
                'short_desc' => [
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
}
