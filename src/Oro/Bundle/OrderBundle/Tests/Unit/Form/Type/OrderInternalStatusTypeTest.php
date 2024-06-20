<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderInternalStatusType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class OrderInternalStatusTypeTest extends FormIntegrationTestCase
{
    private OrderInternalStatusType $type;

    protected function setUp(): void
    {
        $enumValueProvider = $this->createMock(EnumValueProvider::class);
        $enumValueProvider->expects(self::any())
            ->method('getEnumChoicesByCode')
            ->with(Order::INTERNAL_STATUS_CODE)
            ->willReturn(['Open' => 'open', 'Cancelled' => 'cancelled']);

        $this->type = new OrderInternalStatusType($enumValueProvider);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [OrderInternalStatusType::class => $this->type],
                []
            ),
        ];
    }

    public function testGetParent(): void
    {
        self::assertEquals(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(OrderInternalStatusType::class, []);

        self::assertEquals([], $form->getData());

        $form->submit(['open']);

        self::assertCount(0, $form->getErrors(true));
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals(['open'], $form->getData());
    }

    public function testSubmitWhenInvalidChoice(): void
    {
        $form = $this->factory->create(OrderInternalStatusType::class, []);

        self::assertEquals([], $form->getData());

        $form->submit(['another']);

        self::assertCount(1, $form->getErrors(true));
        self::assertEquals('The selected choice is invalid.', $form->getErrors()[0]->getMessage());
        self::assertEquals(['{{ value }}' => 'another'], $form->getErrors()[0]->getMessageParameters());
        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals([], $form->getData());
    }
}
