<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Visibility\VisibilityMessageFilter;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;

class VisibilityMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    private const TOPIC = 'test_topic';

    /** @var VisibilityMessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new VisibilityMessageFilter(self::TOPIC);
    }

    public function testApplyForEmptyBuffer()
    {
        $buffer = new MessageBuffer();
        $this->filter->apply($buffer);
        $this->assertEquals([], $buffer->getMessages());
    }

    public function testApply()
    {
        $buffer = new MessageBuffer();

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['entity_class_name' => ProductVisibility::class, 'id' => 42]);
        $buffer->addMessage(self::TOPIC, ['entity_class_name' => ProductVisibility::class, 'id' => 42]);

        $buffer->addMessage(self::TOPIC, ['entity_class_name' => CustomerProductVisibility::class, 'id' => 123]);

        // add same message twice
        $buffer->addMessage(self::TOPIC, ['entity_class_name' => CustomerGroupProductVisibility::class, 'id' => 321]);
        $buffer->addMessage(self::TOPIC, ['entity_class_name' => CustomerGroupProductVisibility::class, 'id' => 321]);

        // add message without ID
        $buffer->addMessage(
            self::TOPIC,
            [
                'entity_class_name' => ProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id'         => 5,
                'scope_id'          => 1
            ]
        );

        // add same message twice (without ID)
        $buffer->addMessage(
            self::TOPIC,
            [
                'entity_class_name' => ProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id'         => 10,
                'scope_id'          => 1
            ]
        );
        $buffer->addMessage(
            self::TOPIC,
            [
                'entity_class_name' => ProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id'         => 10,
                'scope_id'          => 1
            ]
        );

        $this->filter->apply($buffer);

        $this->assertEquals(
            [
                0 => [self::TOPIC, ['entity_class_name' => ProductVisibility::class, 'id' => 42]],
                2 => [self::TOPIC, ['entity_class_name' => CustomerProductVisibility::class, 'id' => 123]],
                3 => [self::TOPIC, ['entity_class_name' => CustomerGroupProductVisibility::class, 'id' => 321]],
                5 => [
                    self::TOPIC,
                    [
                        'entity_class_name' => ProductVisibility::class,
                        'target_class_name' => Product::class,
                        'target_id'         => 5,
                        'scope_id'          => 1
                    ]
                ],
                6 => [
                    self::TOPIC,
                    [
                        'entity_class_name' => ProductVisibility::class,
                        'target_class_name' => Product::class,
                        'target_id'         => 10,
                        'scope_id'          => 1
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }
}
