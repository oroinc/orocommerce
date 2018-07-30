<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event\Decorator;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\Decorator\ProductVariantIndexEntityEventDecorator;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductVariantIndexEntityEventDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Product */
    private $mainProduct;

    /** @var Product */
    private $childProduct;

    /** @var IndexEntityEvent */
    private $originalEvent;

    /** @var ProductVariantIndexEntityEventDecorator */
    private $decoratorEvent;

    protected function setUp()
    {
        $this->mainProduct = $this->getEntity(Product::class, ['id' => 1]);
        $this->childProduct = $this->getEntity(Product::class, ['id' => 2]);

        $this->originalEvent = new IndexEntityEvent(Product::class, [$this->mainProduct], ['key' => 'value']);

        $this->decoratorEvent = new ProductVariantIndexEntityEventDecorator(
            $this->originalEvent,
            $this->mainProduct->getId(),
            [$this->childProduct]
        );
    }

    public function testConstruct()
    {
        $this->assertEquals($this->originalEvent->getEntityClass(), $this->decoratorEvent->getEntityClass());
        $this->assertEquals([$this->childProduct], $this->decoratorEvent->getEntities());
        $this->assertEquals($this->originalEvent->getContext(), $this->decoratorEvent->getContext());
        $this->assertAttributeEquals($this->originalEvent, 'decoratedEvent', $this->decoratorEvent);
        $this->assertAttributeEquals($this->mainProduct->getId(), 'sourceEntityId', $this->decoratorEvent);
    }

    public function testAddFieldAllTextField()
    {
        $allTextValue = 'all text value';
        $allTextLocalizedValue = 'localized all text value';

        $this->decoratorEvent->addField(
            $this->childProduct->getId(),
            IndexDataProvider::ALL_TEXT_FIELD,
            $allTextValue,
            true
        );
        $this->decoratorEvent->addField(
            $this->childProduct->getId(),
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            $allTextLocalizedValue,
            true
        );

        $expectedData = [
            $this->mainProduct->getId() => [
                IndexDataProvider::ALL_TEXT_FIELD => [
                    ['value' => $allTextValue, 'all_text' => true]
                ],
                IndexDataProvider::ALL_TEXT_L10N_FIELD => [
                    ['value' => $allTextLocalizedValue, 'all_text' => true]
                ],
            ]
        ];
        $this->assertEquals($expectedData, $this->originalEvent->getEntitiesData());
    }

    public function testAddFieldAddedToAllTextField()
    {
        $field = 'sku';
        $value = 'child_sku_value';

        $this->decoratorEvent->addField(
            $this->childProduct->getId(),
            $field,
            $value,
            true
        );

        $expectedData = [
            $this->mainProduct->getId() => [
                IndexDataProvider::ALL_TEXT_L10N_FIELD => [
                    ['value' => $value, 'all_text' => true]
                ],
            ]
        ];
        $this->assertEquals($expectedData, $this->originalEvent->getEntitiesData());
    }

    public function testAddFieldNotAddedToAllTextField()
    {
        $field = 'attribute';
        $value = 'attribute_value';

        $this->decoratorEvent->addField(
            $this->childProduct->getId(),
            $field,
            $value,
            false
        );

        $this->assertEmpty($this->originalEvent->getEntitiesData());
    }

    public function testAddPlaceholderFieldAllTextField()
    {
        $allTextValue = 'all text value';
        $allTextPlaceholders = ['first' => 1];
        $allTextLocalizedValue = 'localized all text value';
        $allTextLocalizedPlaceholders = ['second' => 2];

        $this->decoratorEvent->addPlaceholderField(
            $this->childProduct->getId(),
            IndexDataProvider::ALL_TEXT_FIELD,
            $allTextValue,
            $allTextPlaceholders,
            true
        );
        $this->decoratorEvent->addPlaceholderField(
            $this->childProduct->getId(),
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            $allTextLocalizedValue,
            $allTextLocalizedPlaceholders,
            true
        );

        $expectedData = [
            $this->mainProduct->getId() => [
                IndexDataProvider::ALL_TEXT_FIELD => [
                    [
                        'value' => new PlaceholderValue($allTextValue, $allTextPlaceholders),
                        'all_text' => true
                    ]
                ],
                IndexDataProvider::ALL_TEXT_L10N_FIELD => [
                    [
                        'value' => new PlaceholderValue($allTextLocalizedValue, $allTextLocalizedPlaceholders),
                        'all_text' => true
                    ]
                ],
            ]
        ];
        $this->assertEquals($expectedData, $this->originalEvent->getEntitiesData());
    }

    public function testAddPlaceholderFieldAddedToAllTextField()
    {
        $field = 'description';
        $value = 'description value';
        $placeholders = ['key' => 'value'];

        $this->decoratorEvent->addPlaceholderField(
            $this->childProduct->getId(),
            $field,
            $value,
            $placeholders,
            true
        );

        $expectedData = [
            $this->mainProduct->getId() => [
                IndexDataProvider::ALL_TEXT_L10N_FIELD => [
                    ['value' => new PlaceholderValue($value, $placeholders), 'all_text' => true]
                ],
            ]
        ];
        $this->assertEquals($expectedData, $this->originalEvent->getEntitiesData());
    }

    public function testAddPlaceholderFieldNotAddedToAllTextField()
    {
        $field = 'inventory';
        $value = 'inventory_value';
        $placeholders = ['key' => 'value'];

        $this->decoratorEvent->addPlaceholderField(
            $this->childProduct->getId(),
            $field,
            $value,
            $placeholders,
            false
        );

        $this->assertEmpty($this->originalEvent->getEntitiesData());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method getEntitiesData must never be called. Please, use original event instead.
     */
    public function testGetEntitiesData()
    {
        // original event must work properly
        $this->assertEmpty($this->originalEvent->getEntitiesData());

        // decorator has to trigger an exception
        $this->decoratorEvent->getEntitiesData();
    }
}
