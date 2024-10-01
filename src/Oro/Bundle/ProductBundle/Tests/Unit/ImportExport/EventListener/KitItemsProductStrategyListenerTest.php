<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\EventListener\KitItemsProductDataConverterEventListener;
use Oro\Bundle\ProductBundle\ImportExport\EventListener\KitItemsProductStrategyListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductImageStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class KitItemsProductStrategyListenerTest extends TestCase
{
    private ImportStrategyHelper|MockObject $strategyHelper;
    private TranslatorInterface|MockObject $translator;
    private StrategyInterface|MockObject $strategy;
    private ContextInterface $context;

    private KitItemsProductStrategyListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->strategy = $this->createMock(StrategyInterface::class);
        $this->context = new Context([]);

        $this->listener = new KitItemsProductStrategyListener($this->strategyHelper, $this->translator);
    }

    /**
     * @dataProvider onProcessBeforeDataProvider
     */
    public function testOnProcessBefore(array $expected, object|null $entity): void
    {
        $event = new StrategyEvent($this->strategy, $entity, $this->context);

        $this->listener->onProcessBefore($event);

        self::assertEquals($expected, ReflectionUtil::getPropertyValue($this->listener, 'kitItemsIds'));
    }

    public function testOnProcessBeforeWhenKitItemsWithExtraFields(): void
    {
        $unknownFields = ['sample_field1', 'sample_field2'];
        $this->context->setValue(
            KitItemsProductDataConverterEventListener::KIT_ITEMS_EXTRA_FIELDS,
            [1 => $unknownFields]
        );

        $error = 'oro.product.import.kit_item.unknown_fields [TRANSLATED]';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.import.kit_item.unknown_fields',
                ['%line%' => 2, '%count%' => 2, '{{ fields }}' => implode(', ', $unknownFields)],
                'validators'
            )
            ->willReturn($error);

        $this->strategyHelper->expects(self::once())
            ->method('addValidationErrors')
            ->with([$error], $this->context);

        $product = (new ProductStub())
            ->setType(Product::TYPE_KIT);
        $event = new StrategyEvent($this->strategy, $product, $this->context);
        $this->listener->onProcessBefore($event);

        self::assertEquals(null, $event->getEntity());
    }

    public function testOnProcessBeforeWhenKitItemsWithWrongOptionalValues(): void
    {
        $this->context->setValue(
            KitItemsProductDataConverterEventListener::KIT_ITEMS_INVALID_VALUES,
            [0 => ['optional' => 'invalid']]
        );

        $error = 'oro.product.import.kit_item.incorrect_optional [TRANSLATED]';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.import.kit_item.invalid_value.optional',
                ['%line%' => 1, '{{ field }}' => 'optional', '{{ value }}' => 'invalid'],
                'validators'
            )
            ->willReturn($error);

        $this->strategyHelper->expects(self::once())
            ->method('addValidationErrors')
            ->with([$error], $this->context);

        $product = (new ProductStub())
            ->setType(Product::TYPE_KIT);
        $event = new StrategyEvent($this->strategy, $product, $this->context);
        $this->listener->onProcessBefore($event);

        self::assertEquals(null, $event->getEntity());
    }

    /**
     * @dataProvider onProcessAfterDataProvider
     */
    public function testOnProcessAfter(object|null $expected, object|null $entity): void
    {
        $event = new StrategyEvent($this->strategy, $entity, $this->context);

        $this->listener->onProcessAfter($event);

        self::assertEquals($expected, $event->getEntity());
    }

    public function testOnProcessAfterWithKitItems(): void
    {
        $kitItem = new ProductKitItemStub(3);

        $product = new ProductStub();
        $product->setId(5);
        $product->setType(ProductStub::TYPE_KIT);
        $product->addKitItem(new ProductKitItemStub());
        $product->addKitItem(new ProductKitItemStub(1));
        $product->addKitItem(new ProductKitItemStub(2));
        $product->addKitItem($kitItem);

        $event = new StrategyEvent($this->strategy, $product, $this->context);
        $this->listener->onProcessBefore($event);

        $product->removeKitItem($kitItem);

        $error = 'KitItem with #3 ID was not found.';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.product.import.kit_item.not_found', ['%id%' => 3], 'validators')
            ->willReturn($error);

        $this->strategyHelper->expects(self::once())
            ->method('addValidationErrors')
            ->with([$error], $this->context);

        $this->listener->onProcessAfter($event);

        self::assertEquals(null, $event->getEntity());
        self::assertEquals([], ReflectionUtil::getPropertyValue($this->listener, 'kitItemsIds'));
    }

    public function onProcessBeforeDataProvider(): array
    {
        $product = new ProductStub();
        $product->setType(ProductStub::TYPE_KIT);
        $product->addKitItem(new ProductKitItemStub(1));
        $product->addKitItem(new ProductKitItemStub(2));

        return [
            'Entity is null' => [
                'expected' => [],
                'entity' => null,
            ],
            'Not applicable Entity' => [
                'expected' => [],
                'entity' => new ProductImageStub(),
            ],
            'Product with wrong type' => [
                'expected' => [],
                'entity' => new ProductStub(),
            ],
            'Product without KitItems' => [
                'expected' => [],
                'entity' => (new ProductStub())->setType(ProductStub::TYPE_KIT),
            ],
            'Product with KitItems' => [
                'expected' => [1, 2],
                'entity' => $product,
            ],
        ];
    }

    public function onProcessAfterDataProvider(): array
    {
        $product = new ProductStub();
        $product->setType(ProductStub::TYPE_KIT);

        return [
            'Entity is null' => [
                'expected' => null,
                'entity' => null,
            ],
            'Not applicable Entity' => [
                'expected' => new ProductImageStub(),
                'entity' => new ProductImageStub(),
            ],
            'Product with wrong type' => [
                'expected' => new ProductStub(),
                'entity' => new ProductStub(),
            ],
            'Product without ID' => [
                'expected' => $product,
                'entity' => $product,
            ],
            'Product without KitItems' => [
                'expected' => new ProductStub(1),
                'entity' => new ProductStub(1),
            ],
        ];
    }
}
