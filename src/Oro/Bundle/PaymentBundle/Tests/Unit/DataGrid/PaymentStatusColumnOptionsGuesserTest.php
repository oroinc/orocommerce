<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DataGrid;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\PaymentBundle\DataGrid\PaymentStatusColumnOptionsGuesser;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusVirtualRelationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Guess\Guess;

final class PaymentStatusColumnOptionsGuesserTest extends TestCase
{
    private PaymentStatusColumnOptionsGuesser $guesser;

    protected function setUp(): void
    {
        $this->guesser = new PaymentStatusColumnOptionsGuesser();
    }

    public function testGuessFilterWithValidPaymentStatusClass(): void
    {
        $result = $this->guesser->guessFilter(
            PaymentStatus::class,
            PaymentStatusVirtualRelationProvider::VIRTUAL_RELATION_NAME,
            'string'
        );

        self::assertInstanceOf(ColumnGuess::class, $result);
        self::assertEquals(Guess::HIGH_CONFIDENCE, $result->getConfidence());

        $options = $result->getOptions();
        self::assertEquals(PaymentStatusFilter::NAME, $options['type']);
        self::assertEquals('entity.paymentStatus', $options['data_name']);
        self::assertTrue($options['options']['raw_labels']);
    }

    public function testGuessFilterWithInvalidClass(): void
    {
        $result = $this->guesser->guessFilter(
            'SomeOtherClass',
            PaymentStatusVirtualRelationProvider::VIRTUAL_RELATION_NAME,
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessFilterWithInvalidProperty(): void
    {
        $result = $this->guesser->guessFilter(PaymentStatus::class, 'otherProperty', 'string');

        self::assertNull($result);
    }

    public function testGuessFormatterReturnsNull(): void
    {
        $result = $this->guesser->guessFormatter(
            PaymentStatus::class,
            PaymentStatusVirtualRelationProvider::VIRTUAL_RELATION_NAME,
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessSorterReturnsNull(): void
    {
        $result = $this->guesser->guessSorter(
            PaymentStatus::class,
            PaymentStatusVirtualRelationProvider::VIRTUAL_RELATION_NAME,
            'string'
        );

        self::assertNull($result);
    }
}
