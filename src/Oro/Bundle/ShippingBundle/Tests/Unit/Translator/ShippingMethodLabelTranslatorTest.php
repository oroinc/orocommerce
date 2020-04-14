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

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formatter = $this->createMock(ShippingMethodLabelFormatter::class);

        $this->shippingMethodTranslator = new ShippingMethodLabelTranslator(
            $this->formatter,
            $this->translator
        );
    }

    public function testGetShippingMethodWithTypeLabel()
    {
        $label = 'test';
        $labelTranslated = 'translated';

        $this->formatter->expects(static::once())
            ->method('formatShippingMethodWithTypeLabel')
            ->willReturn($label);

        $this->translator->expects(static::once())
            ->method('trans')
            ->with($label)
            ->willReturn($labelTranslated);

        self::assertSame(
            $labelTranslated,
            $this->shippingMethodTranslator->getShippingMethodWithTypeLabel('', '')
        );
    }
}
