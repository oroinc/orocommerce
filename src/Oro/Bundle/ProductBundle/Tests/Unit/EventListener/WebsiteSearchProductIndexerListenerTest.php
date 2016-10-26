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
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
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
    private function prepareLocalizedValue($localization = null, $string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value
            ->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    /**
     * @param Localization $firstLocale
     * @param Localization $secondLocale
     * @return Product
     */
    private function prepareProduct($firstLocale, $secondLocale)
    {
        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryStatus->expects($this->once())->method('getId')->willReturn(Product::INVENTORY_STATUS_IN_STOCK);

        $product = $this->getEntity(
            Product::class,
            [
                'id' => 777,
                'sku' => 'sku123',
                'status' => Product::STATUS_ENABLED,
                'inventoryStatus' => $inventoryStatus,
            ]
        );

        $product
            ->addName($this->prepareLocalizedValue($firstLocale, self::NAME_DEFAULT_LOCALE, null))
            ->addName($this->prepareLocalizedValue($secondLocale, self::NAME_CUSTOM_LOCALE, null))
            ->addName($this->prepareLocalizedValue(null, 'Default name', null))
            ->addDescription($this->prepareLocalizedValue($firstLocale, null, self::DESCRIPTION_DEFAULT_LOCALE))
            ->addDescription($this->prepareLocalizedValue($secondLocale, null, self::DESCRIPTION_CUSTOM_LOCALE))
            ->addDescription($this->prepareLocalizedValue(null, 'Default description', null))
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    $firstLocale,
                    null,
                    self::SHORT_DESCRIPTION_DEFAULT_LOCALE
                )
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    $secondLocale,
                    null,
                    self::SHORT_DESCRIPTION_CUSTOM_LOCALE
                )
            )
            ->addShortDescription(
                $this->prepareLocalizedValue(
                    null,
                    'Default short description',
                    null
                )
            );

        return $product;
    }

    public function testOnWebsiteSearchIndexProductClass()
    {
        /** @var Localization $firstLocale */
        $firstLocale = $this->getEntity(Localization::class, ['id' => 1]);

        /** @var Localization $secondLocale */
        $secondLocale = $this->getEntity(Localization::class, ['id' => 2]);

        $this->websiteLocalizationProvider
            ->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn(
                [
                    $firstLocale,
                    $secondLocale,
                ]
            );

        $product = $this->prepareProduct($firstLocale, $secondLocale);

        $event = new IndexEntityEvent([$product], []);

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()] = [
            'sku' => 'sku123',
            'sku_uppercase' => 'SKU123',
            'status' => Product::STATUS_ENABLED,
            'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
            'title_LOCALIZATION_ID' => [
                new PlaceholderValue(
                    $this->prepareLocalizedValue(null, 'Default name', null),
                    [LocalizationIdPlaceholder::NAME => Localization::DEFAULT_LOCALIZATION]
                ),
                new PlaceholderValue(
                    $this->prepareLocalizedValue($firstLocale, self::NAME_DEFAULT_LOCALE, null),
                    [LocalizationIdPlaceholder::NAME => $firstLocale->getId()]
                ),
                new PlaceholderValue(
                    $this->prepareLocalizedValue($secondLocale, self::NAME_CUSTOM_LOCALE, null),
                    [LocalizationIdPlaceholder::NAME => $secondLocale->getId()]
                ),
            ],
            'description_LOCALIZATION_ID' => [
                new PlaceholderValue(
                    $this->prepareLocalizedValue(null, 'Default description', null),
                    [LocalizationIdPlaceholder::NAME => Localization::DEFAULT_LOCALIZATION]
                ),
                new PlaceholderValue(
                    $this->prepareLocalizedValue($firstLocale, null, self::DESCRIPTION_DEFAULT_LOCALE),
                    [LocalizationIdPlaceholder::NAME => $firstLocale->getId()]
                ),
                new PlaceholderValue(
                    $this->prepareLocalizedValue($secondLocale, null, self::DESCRIPTION_CUSTOM_LOCALE),
                    [LocalizationIdPlaceholder::NAME => $secondLocale->getId()]
                ),
            ],
            'short_description_LOCALIZATION_ID' => [
                new PlaceholderValue(
                    $this->prepareLocalizedValue(null, 'Default short description', null),
                    [LocalizationIdPlaceholder::NAME => Localization::DEFAULT_LOCALIZATION]
                ),
                new PlaceholderValue(
                    $this->prepareLocalizedValue($firstLocale, null, self::SHORT_DESCRIPTION_DEFAULT_LOCALE),
                    [LocalizationIdPlaceholder::NAME => $firstLocale->getId()]
                ),
                new PlaceholderValue(
                    $this->prepareLocalizedValue($secondLocale, null, self::SHORT_DESCRIPTION_CUSTOM_LOCALE),
                    [LocalizationIdPlaceholder::NAME => $secondLocale->getId()]
                ),
            ],
        ];

        $this->assertEquals($expected, $event->getEntitiesData());
    }
}
