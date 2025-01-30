<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Provider\ProductKitItemEntityNameProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub as ProductKitItem;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\ReflectionUtil;

class ProductKitItemEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitItemEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ProductKitItemEntityNameProvider();
    }

    private function getProductKitItemLabel(string $string, ?Localization $localization = null): ProductKitItemLabel
    {
        $value = new ProductKitItemLabel();
        $value->setString($string);
        $value->setLocalization($localization);

        return $value;
    }

    private function getLocalization(string $code): Localization
    {
        $language = new Language();
        $language->setCode($code);

        $localization = new Localization();
        ReflectionUtil::setId($localization, 123);
        $localization->setLanguage($language);

        return $localization;
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $item = new ProductKitItem();
        $item->addLabel($this->getProductKitItemLabel('default label'));
        $item->addLabel($this->getProductKitItemLabel('localized label', $this->getLocalization('en')));

        $this->assertEquals(
            'default label',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $item)
        );
    }

    public function testGetNameForLocalization(): void
    {
        $item = new ProductKitItem();
        $item->addLabel($this->getProductKitItemLabel('default label'));
        $item->addLabel($this->getProductKitItemLabel('localized label', $this->getLocalization('en')));

        $this->assertEquals(
            'localized label',
            $this->provider->getName(EntityNameProviderInterface::FULL, $this->getLocalization('en'), $item)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            'CAST((SELECT item_l.string'
            . ' FROM Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel item_l'
            . ' WHERE item_l MEMBER OF item.labels AND item_l.localization IS NULL) AS string)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, ProductKitItem::class, 'item')
        );
    }

    public function testGetNameDQLForLocalization(): void
    {
        self::assertEquals(
            'CAST((SELECT COALESCE(item_l.string, item_dl.string)'
            . ' FROM Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel item_dl'
            . ' LEFT JOIN Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel item_l'
            . ' WITH item_l MEMBER OF item.labels AND item_l.localization = 123'
            . ' WHERE item_dl MEMBER OF item.labels AND item_dl.localization IS NULL) AS string)',
            $this->provider->getNameDQL(
                EntityNameProviderInterface::FULL,
                $this->getLocalization('en'),
                ProductKitItem::class,
                'item'
            )
        );
    }
}
