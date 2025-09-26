<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider\EmailTemplate;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\EmailTemplate\OrderPdfFileVariableProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderPdfFileVariableProviderTest extends TestCase
{
    private const string PDF_VARIABLE_NAME = 'orderDefaultPdfFile';
    private const string PDF_VARIABLE_SNAKE_NAME = 'order_default_pdf_file';
    private const string TRANSLATED_LABEL = 'Order Default PDF File';

    private OrderPdfFileVariableProvider $provider;

    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new OrderPdfFileVariableProvider(
            $this->translator,
            self::PDF_VARIABLE_NAME
        );
    }

    public function testGetVariableDefinitions(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.order.email_template.variables.' . self::PDF_VARIABLE_SNAKE_NAME)
            ->willReturn(self::TRANSLATED_LABEL);

        $expected = [
            Order::class => [
                self::PDF_VARIABLE_NAME => [
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                    'label' => self::TRANSLATED_LABEL,
                ],
            ],
        ];

        self::assertEquals($expected, $this->provider->getVariableDefinitions());
    }

    public function testGetVariableGetters(): void
    {
        $expected = [
            Order::class => [
                self::PDF_VARIABLE_NAME => null,
            ],
        ];

        self::assertEquals($expected, $this->provider->getVariableGetters());
    }

    public function testGetVariableProcessorsForOrderClass(): void
    {
        $expected = [
            self::PDF_VARIABLE_NAME => [
                'processor' => self::PDF_VARIABLE_SNAKE_NAME,
            ],
        ];

        self::assertEquals($expected, $this->provider->getVariableProcessors(Order::class));
    }

    public function testGetVariableProcessorsForNonOrderClass(): void
    {
        self::assertEquals([], $this->provider->getVariableProcessors('SomeOtherClass'));
    }
}
