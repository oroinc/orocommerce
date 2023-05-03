<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\OrderBundle\Form\Type\OrderInternalStatusType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;

class OrderInternalStatusTypeTest extends FormIntegrationTestCase
{
    private OrderInternalStatusType $type;

    private EnumValueProvider|MockObject $enumValueProvider;

    protected function setUp(): void
    {
        $this->enumValueProvider = $this->createMock(EnumValueProvider::class);

        $this->type = new OrderInternalStatusType(
            $this->enumValueProvider
        );

        parent::setUp();
    }

    public function testGetParent(): void
    {
        self::assertEquals(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testSubmit(): void
    {
        $this->enumValueProvider->expects(self::once())
            ->method('getEnumChoicesByCode')
            ->with('order_internal_status')
            ->willReturn(['Open' => 'open', 'Cancelled' => 'cancelled']);

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
        $this->enumValueProvider->expects(self::once())
            ->method('getEnumChoicesByCode')
            ->with('order_internal_status')
            ->willReturn(['Open' => 'open', 'Cancelled' => 'cancelled']);

        $form = $this->factory->create(OrderInternalStatusType::class, []);

        self::assertEquals([], $form->getData());

        $form->submit(['archived']);

        self::assertCount(1, $form->getErrors(true));
        self::assertEquals('This value is not valid.', $form->getErrors()[0]->getMessage());
        self::assertEquals(['{{ value }}' => 'archived'], $form->getErrors()[0]->getMessageParameters());
        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals([], $form->getData());
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    OrderInternalStatusType::class => $this->type,
                ],
                []
            ),
        ];
    }
}
