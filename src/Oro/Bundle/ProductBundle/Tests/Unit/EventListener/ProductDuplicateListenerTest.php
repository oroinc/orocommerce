<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Stub\ExtendProductStub as Product;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;
use PHPUnit\Framework\TestCase;

class ProductDuplicateListenerTest extends TestCase
{
    use PropertyAccessTrait;

    /** @var ConfigManager */
    private $configManager;

    /** @var ConfigProvider */
    private $attributeProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductDuplicateListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->attributeProvider = $this->createMock(ConfigProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new ProductDuplicateListener(
            $this->configManager,
            $this->attributeProvider,
            $this->doctrineHelper,
            $this->getPropertyAccessor(),
        );
    }

    public function testDuplicateAfter()
    {
        $sourceProduct = $this->getProduct([
            'id' => 1,
            'sku' => 'SKU1',
            'titleManyToOne' => $this->getLocalizedFallbackValue(1, 'title'),
            'titleManyToMany' => [
                $this->getLocalizedFallbackValue(2, 'titles_2'),
                $this->getLocalizedFallbackValue(3, 'titles_3')
            ],
            'titleOneToMany' => [
                $this->getLocalizedFallbackValue(4, 'titles_4'),
                $this->getLocalizedFallbackValue(5, 'titles_5'),
                $this->getLocalizedFallbackValue(6, 'titles_6'),
            ],
        ]);

        $product = $this->getProduct(['id' => 2, 'sku' => 'SKU2']);
        $expectedProduct = $this->getProduct([
            'id' => 2,
            'sku' => 'SKU2',
            'titleManyToOne' => $this->getLocalizedFallbackValue(null, 'title'),
            'titleManyToMany' => [
                $this->getLocalizedFallbackValue(null, 'titles_2'),
                $this->getLocalizedFallbackValue(null, 'titles_3')
            ],
            'titleOneToMany' => [
                $this->getLocalizedFallbackValue(null, 'titles_4'),
                $this->getLocalizedFallbackValue(null, 'titles_5'),
                $this->getLocalizedFallbackValue(null, 'titles_6'),
            ],
        ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('flush')
            ->with($expectedProduct);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects(self::once())
            ->method('filter')
            ->willReturn([
                $this->getFieldConfig('titleManyToOne', 'oneToMany'),
                $this->getFieldConfig('titleManyToMany', 'manyToMany'),
                $this->getFieldConfig('titleOneToMany', 'oneToMany'),
            ]);


        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        $this->attributeProvider->expects(self::exactly(3))
            ->method('getConfig')
            ->willReturnCallback(function (string $className, string $attributeName) {
                return new Config(
                    new FieldConfigId('scope', $className, $attributeName),
                    [
                        'field_name' => $attributeName,
                    ]
                );
            });

        $event = new ProductDuplicateAfterEvent($product, $sourceProduct);
        $this->listener->onDuplicateAfter($event);

        $titleManyToMany = $product->getTitleManyToMany();
        self::assertEquals('SKU2', $product->getSku());
        self::assertNull($product->getTitleManyToOne()->getId());
        self::assertEquals(2, \count($titleManyToMany));
        self::assertEquals(
            ['titles_2', 'titles_3'],
            array_map(fn (LocalizedFallbackValue $value) => $value->getString(), $titleManyToMany)
        );
        self::assertEquals(
            [null, null],
            array_map(fn (LocalizedFallbackValue $value) => $value->getId(), $titleManyToMany)
        );

        $titleOneToMany = $product->getTitleOneToMany();
        self::assertEquals(3, \count($titleOneToMany));
        self::assertEquals(
            ['titles_4', 'titles_5', 'titles_6'],
            array_map(fn (LocalizedFallbackValue $value) => $value->getString(), $titleOneToMany)
        );
        self::assertEquals(
            [null, null, null],
            array_map(fn (LocalizedFallbackValue $value) => $value->getId(), $titleOneToMany)
        );
    }

    public function testDuplicateAfterWithoutExtendAttributes()
    {
        $sourceProduct = $this->getProduct([
            'id' => 1,
            'sku' => 'SKU1',
            'titleManyToOne' => $this->getLocalizedFallbackValue(1, 'title'),
            'titleManyToMany' => [
                $this->getLocalizedFallbackValue(2, 'titles_2'),
                $this->getLocalizedFallbackValue(3, 'titles_3')
            ],
            'titleOneToMany' => [
                $this->getLocalizedFallbackValue(4, 'titles_4'),
                $this->getLocalizedFallbackValue(5, 'titles_5'),
                $this->getLocalizedFallbackValue(6, 'titles_6'),
            ],
        ]);

        $product = $this->getProduct(['id' => 2, 'sku' => 'SKU2']);
        $expectedProduct = $this->getProduct(['id' => 2,'sku' => 'SKU2']);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('flush')
            ->with($expectedProduct);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects(self::once())
            ->method('filter')
            ->willReturn([]);

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        $this->attributeProvider->expects(self::never())
            ->method('getConfig');

        $event = new ProductDuplicateAfterEvent($product, $sourceProduct);
        $this->listener->onDuplicateAfter($event);

        self::assertEquals('SKU2', $product->getSku());
        self::assertNull($product->getTitleManyToOne());
        self::assertEmpty($product->getTitleManyToMany());
        self::assertEmpty($product->getTitleOneToMany());
    }

    private function getFieldConfig(string $fieldName, string $fieldType): Config
    {
        $id = new FieldConfigId('scope', 'className', $fieldName, $fieldType);

        return new Config($id, [
            'owner' => 'Custom'
        ]);
    }

    private function getProduct(array $attributes): Product
    {
        $product = new Product();
        foreach ($attributes as $attribute => $value) {
            ReflectionUtil::setPropertyValue($product, $attribute, $value);
        }

        return $product;
    }
    private function getLocalizedFallbackValue(?int $id, string $string): LocalizedFallbackValue
    {
        $fallbackValue = new LocalizedFallbackValue();
        ReflectionUtil::setId($fallbackValue, $id);
        $fallbackValue->setString($string);

        return $fallbackValue;
    }
}
