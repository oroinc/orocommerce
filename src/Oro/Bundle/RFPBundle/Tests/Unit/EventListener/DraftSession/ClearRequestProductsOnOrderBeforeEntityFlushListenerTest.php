<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\EntityExtendBundle\Test\ExtendedEntityTestTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\RFPBundle\EventListener\DraftSession\ClearRequestProductsOnOrderBeforeEntityFlushListener;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class ClearRequestProductsOnOrderBeforeEntityFlushListenerTest extends TestCase
{
    use ExtendedEntityTestTrait;

    private ClearRequestProductsOnOrderBeforeEntityFlushListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new ClearRequestProductsOnOrderBeforeEntityFlushListener();
    }

    public function testOnBeforeEntityFlushDoesNothingWhenFeaturesAreDisabled(): void
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->listener->setFeatureChecker($featureChecker);
        $this->listener->addFeature('order_draft_edit_mode');

        $setCalls = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$setCalls): bool {
                $setCalls[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $lineItem = new OrderLineItem();
        $order = new Order();
        $order->addLineItem($lineItem);

        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $order);

        $this->listener->onBeforeEntityFlush($event);

        self::assertEmpty($setCalls);
    }

    public function testOnBeforeEntityFlushIgnoresWhenDataIsNotOrder(): void
    {
        $form = $this->createMock(FormInterface::class);
        $notAnOrder = new \stdClass();
        $event = new AfterFormProcessEvent($form, $notAnOrder);

        $this->expectNotToPerformAssertions();

        $this->listener->onBeforeEntityFlush($event);
    }

    public function testOnBeforeEntityFlushIgnoresWhenDataIsNull(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, null);

        $this->expectNotToPerformAssertions();

        $this->listener->onBeforeEntityFlush($event);
    }

    public function testOnBeforeEntityFlushIgnoresExistingOrder(): void
    {
        $form = $this->createMock(FormInterface::class);

        $lineItem = new OrderLineItem();

        $setCalls = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$setCalls): bool {
                $setCalls[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $order = new Order();
        ReflectionUtil::setId($order, 42);
        $order->addLineItem($lineItem);

        $event = new AfterFormProcessEvent($form, $order);

        $this->listener->onBeforeEntityFlush($event);

        self::assertEmpty($setCalls);
    }

    public function testOnBeforeEntityFlushClearsRequestProductsOnNewOrder(): void
    {
        $form = $this->createMock(FormInterface::class);

        $lineItem1 = new OrderLineItem();
        $lineItem2 = new OrderLineItem();

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $order = new Order();
        $order->addLineItem($lineItem1);
        $order->addLineItem($lineItem2);

        $event = new AfterFormProcessEvent($form, $order);

        $this->listener->onBeforeEntityFlush($event);

        self::assertNull($requestProductValues[spl_object_id($lineItem1)]);
        self::assertNull($requestProductValues[spl_object_id($lineItem2)]);
    }
}
