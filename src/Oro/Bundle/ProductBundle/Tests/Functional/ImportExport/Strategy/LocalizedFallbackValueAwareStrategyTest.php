<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedFallbackValueAwareStrategyTest extends WebTestCase
{
    private LocalizedFallbackValueAwareStrategy $strategy;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductData::class]);

        $container = self::getContainer();
        $container->get('oro_importexport.field.database_helper')->onClear();

        $this->strategy = new LocalizedFallbackValueAwareStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.configurable_import_strategy_helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_importexport.field.related_entity_state_helper')
        );
        $this->strategy->setLocalizedFallbackValueClass(AbstractLocalizedFallbackValue::class);
        $this->strategy->setOwnershipSetter($container->get('oro_organization.entity_ownership_associations_setter'));
    }

    private function getProductUnitPrecision(ProductUnit $unit, int $precision): ProductUnitPrecision
    {
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);
        $unitPrecision->setPrecision($precision);

        return $unitPrecision;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }

    private function getProductName(string $stringValue, ?string $localizationName = null): ProductName
    {
        $productName = new ProductName();
        $productName->setString($stringValue);
        if (null !== $localizationName) {
            $productName->setLocalization($this->getLocalization($localizationName));
        }

        return $productName;
    }

    private function getProductShortDescription(
        ?string $fallback,
        ?string $textValue,
        string $localizationReference
    ): ProductShortDescription {
        $description = new ProductShortDescription();
        if (null !== $fallback) {
            $description->setFallback($fallback);
        }
        if (null !== $textValue) {
            $description->setText($textValue);
        }
        $description->setLocalization($this->getReference($localizationReference));

        return $description;
    }

    private function getLocalizedValue(string $stringValue, ?string $localizationName = null): LocalizedFallbackValue
    {
        $localizedValue = new LocalizedFallbackValue();
        $localizedValue->setString($stringValue);
        if (null !== $localizationName) {
            $localizedValue->setLocalization($this->getLocalization($localizationName));
        }

        return $localizedValue;
    }

    private function getLocalization(string $name): Localization
    {
        $localization = new Localization();
        $localization->setName($name);

        return $localization;
    }

    private function loadDefaultAttributeFamily(): AttributeFamily
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => 'default_family']);
    }

    private function loadInventoryStatus(string $id): AbstractEnumValue
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(ExtendHelper::buildEnumValueClassName('prod_inventory_status'))
            ->find($id);
    }

    private function loadFirstBusinessUnit(): BusinessUnit
    {
        return self::getContainer()->get('doctrine')->getRepository(BusinessUnit::class)->findOneBy([]);
    }

    public function testProcess(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'names' => [
                null => ['string' => 'product-1 Default Name'],
                'English (Canada)' => ['string' => 'product-1 en_CA Name']
            ],
            'slugPrototypes' => [
                null => ['string' => 'product-1-default-slug-prototype-updated'],
                'English (Canada)' => ['string' => 'product-1-en-ca-slug-prototype-added']
            ],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        /** @var Product $existingEntity */
        $existingEntity = $this->getReference('product-1');
        $this->assertNotEmpty($existingEntity->getNames());
        $this->assertNotEmpty($existingEntity->getSlugPrototypes());

        $entity = new Product();
        $entity->setSku('product-1');
        $entity->setAttributeFamily($this->loadDefaultAttributeFamily());
        $entity->setPrimaryUnitPrecision($this->getProductUnitPrecision($this->getProductUnit('kg'), 3));
        $entity->addName($this->getProductName('product-1 Default Name'));
        $entity->addName($this->getProductName('product-1 en_CA Name', 'English (Canada)'));
        $entity->addSlugPrototype($this->getLocalizedValue('product-1-default-slug-prototype-updated'));
        $entity->addSlugPrototype($this->getLocalizedValue('product-1-en-ca-slug-prototype-added', 'English (Canada)'));
        $entity->setInventoryStatus($this->loadInventoryStatus('in_stock'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $expectedNames = [
            'default' => [
                'reference' => 'product-1.names.default',
                'string' => 'product-1 Default Name',
                'text' => null,
                'fallback' => null,
            ],
            'English (Canada)' => [
                'reference' => 'product-1.names.en_CA',
                'string' => 'product-1 en_CA Name',
                'text' => null,
                'fallback' => null,
            ],
        ];
        $expectedSlugPrototypes = [
            'default' => [
                'reference' => 'product-1.slugPrototypes.default',
                'string' => 'product-1-default-slug-prototype-updated',
                'text' => null,
                'fallback' => null,
            ],
            'English (Canada)' => [
                'reference' => 'product-1.slugPrototypes.en_CA',
                'string' => 'product-1-en-ca-slug-prototype-added',
                'text' => null,
                'fallback' => null,
            ],
        ];
        $this->assertLocalizedFallbackValues($expectedNames, $result->getNames());
        $this->assertLocalizedFallbackValues($expectedSlugPrototypes, $result->getSlugPrototypes());
    }

    public function testProcessSkippedNewProductWillNotBeImportedIfNamesAreEmpty(): void
    {
        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('new_sku');
        $entity->setAttributeFamily($this->loadDefaultAttributeFamily());
        $entity->setPrimaryUnitPrecision($this->getProductUnitPrecision($this->getProductUnit('kg'), 3));
        $entity->setInventoryStatus($this->loadInventoryStatus('in_stock'));
        $entity->setOwner($this->loadFirstBusinessUnit());

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertNull($result);
    }

    public function testProcessSkippedExistingProductWithIdNotMappedForNewFallback(): void
    {
        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-4');
        $entity->setAttributeFamily($this->loadDefaultAttributeFamily());
        $entity->setPrimaryUnitPrecision($this->getProductUnitPrecision($this->getProductUnit('each'), 0));
        $entity->addName($this->getProductName('product-4 Default Name'));
        $entity->addName($this->getProductName('product-4 en_CA Name', 'English (United States)'));
        $entity->setInventoryStatus($this->loadInventoryStatus('in_stock'));
        $entity->setOwner($this->loadFirstBusinessUnit());

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertNotNull($result->getId());
        $this->assertNotEmpty($result->getNames()->toArray());
        $this->assertNull($result->getNames()->last()->getId());
    }

    private function assertLocalizedFallbackValues(array $expectedValues, Collection $actualValues): void
    {
        $this->assertCount(count($expectedValues), $actualValues);
        foreach ($actualValues as $localizedFallbackValue) {
            $localizationCode = LocalizationCodeFormatter::formatName($localizedFallbackValue->getLocalization());
            $this->assertArrayHasKey($localizationCode, $expectedValues);

            $expectedValue = $expectedValues[$localizationCode];
            if (!empty($expectedValue['reference'])) {
                /**
                 * Validate that id matched from existing collection and does not affect other entities
                 * @var LocalizedFallbackValue $reference
                 */
                $reference = $this->getReference($expectedValue['reference']);
                $this->assertEquals($reference->getId(), $localizedFallbackValue->getId());
            } else {
                $this->assertNull($localizedFallbackValue->getId());
            }

            $this->assertEquals($expectedValue['text'], $localizedFallbackValue->getText());
            $this->assertEquals($expectedValue['string'], $localizedFallbackValue->getString());
            $this->assertEquals($expectedValue['fallback'], $localizedFallbackValue->getFallback());
        }
    }

    public function testNewText(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => 'new value', 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $entity->addShortDescription($this->getProductShortDescription(null, 'new value', 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals('new value', $result->getShortDescriptions()->first()->getText());
        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
    }

    public function testNewFallback(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => null, 'fallback' => FallbackType::PARENT_LOCALIZATION]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $entity->addShortDescription($this->getProductShortDescription(FallbackType::PARENT_LOCALIZATION, null, 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(FallbackType::PARENT_LOCALIZATION, $result->getShortDescriptions()->first()->getFallback());
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }

    public function testUpdateText(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => 'product-1.shortDescriptions.en_CA_new', 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $entity->addShortDescription(
            $this->getProductShortDescription(null, 'product-1.shortDescriptions.en_CA_new', 'en_CA')
        );

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            'product-1.shortDescriptions.en_CA_new',
            $result->getShortDescriptions()->first()->getText()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
    }

    public function testUpdateFallback(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => null, 'fallback' => FallbackType::SYSTEM]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $entity->addShortDescription($this->getProductShortDescription(FallbackType::SYSTEM, null, 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            FallbackType::SYSTEM,
            $result->getShortDescriptions()->first()->getFallback()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }

    public function testSwitchTextToFallback(): void
    {
        $itemData = [
            'sku' => 'product-1',
            'shortDescriptions' => [['text' => null, 'fallback' => FallbackType::SYSTEM]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-1');
        $entity->addShortDescription($this->getProductShortDescription(FallbackType::SYSTEM, null, 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            FallbackType::SYSTEM,
            $result->getShortDescriptions()->first()->getFallback()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }

    public function testSwitchFallbackToText(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => 'text', 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $entity->addShortDescription($this->getProductShortDescription(null, 'text', 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertEquals(
            'text',
            $result->getShortDescriptions()->first()->getText()
        );
        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
    }

    public function testBothFallbackAndValueWithValuesAreValid(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => 'text', 'fallback' => FallbackType::SYSTEM]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $entity->addShortDescription($this->getProductShortDescription(FallbackType::SYSTEM, 'text', 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertNotNull($result->getShortDescriptions()->first()->getFallback());
        $this->assertNotNull($result->getShortDescriptions()->first()->getText());
    }

    public function testBothFallbackAndValueWithoutValuesAreValid(): void
    {
        $itemData = [
            'sku' => 'product-2',
            'shortDescriptions' => [['text' => null, 'fallback' => null]],
        ];

        $context = new Context([]);
        $context->setValue('itemData', $itemData);
        $this->strategy->setImportExportContext($context);
        $this->strategy->setEntityName(Product::class);

        $entity = new Product();
        $entity->setSku('product-2');
        $entity->addShortDescription($this->getProductShortDescription(null, null, 'es'));

        /** @var Product $result */
        $result = $this->strategy->process($entity);

        $this->assertEquals([], $context->getErrors());
        $this->assertNotEmpty($result);

        $this->assertNull($result->getShortDescriptions()->first()->getFallback());
        $this->assertNull($result->getShortDescriptions()->first()->getText());
    }
}
