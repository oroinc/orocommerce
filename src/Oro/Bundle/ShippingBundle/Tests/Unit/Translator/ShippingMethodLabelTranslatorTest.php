<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Translator;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShippingMethodLabelTranslatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ShippingMethodLabelTranslator */
    private $shippingMethodTranslator;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formatter = $this->createMock(ShippingMethodLabelFormatter::class);

        $this->shippingMethodTranslator = new ShippingMethodLabelTranslator(
            $this->formatter,
            $this->translator
        );
    }

    /**
     * @dataProvider getShippingMethodWithTypeLabelDataProvider
     */
    public function testGetShippingMethodWithTypeLabel(?string $shippingMethod, ?string $shippingMethodType): void
    {
        $label = 'test';
        $translatedLabel = 'translated';

        $this->formatter->expects(self::once())
            ->method('formatShippingMethodWithTypeLabel')
            ->with($shippingMethod, $shippingMethodType)
            ->willReturn($label);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($label)
            ->willReturn($translatedLabel);

        self::assertSame(
            $translatedLabel,
            $this->shippingMethodTranslator->getShippingMethodWithTypeLabel($shippingMethod, $shippingMethodType)
        );
    }

    public static function getShippingMethodWithTypeLabelDataProvider(): array
    {
        return [
            ['method', 'type'],
            ['method', null],
            [null, 'type'],
            [null, null]
        ];
    }
}
