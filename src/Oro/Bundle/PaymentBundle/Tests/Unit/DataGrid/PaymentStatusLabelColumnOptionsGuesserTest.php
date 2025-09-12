<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DataGrid;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\PaymentBundle\DataGrid\PaymentStatusLabelColumnOptionsGuesser;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusLabelVirtualFieldProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Guess\Guess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusLabelColumnOptionsGuesserTest extends TestCase
{
    private PaymentStatusLabelColumnOptionsGuesser $guesser;
    private string $entityClass;

    protected function setUp(): void
    {
        $this->entityClass = PaymentStatus::class;
        $this->guesser = new PaymentStatusLabelColumnOptionsGuesser($this->entityClass);
    }

    public function testGuessFormatterWithValidPaymentStatusClass(): void
    {
        $result = $this->guesser->guessFormatter(
            PaymentStatus::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertInstanceOf(ColumnGuess::class, $result);
        self::assertEquals(Guess::HIGH_CONFIDENCE, $result->getConfidence());

        $options = $result->getOptions();
        self::assertEquals('twig', $options['type']);
        self::assertEquals('html', $options['frontend_type']);
        self::assertEquals('@OroPayment/DataGrid/Property/paymentStatusLabel.html.twig', $options['template']);
    }

    public function testGuessFormatterWithCustomTemplate(): void
    {
        $customTemplate = '@Custom/template.html.twig';
        $guesser = new PaymentStatusLabelColumnOptionsGuesser($this->entityClass, $customTemplate);

        $result = $guesser->guessFormatter(
            PaymentStatus::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertInstanceOf(ColumnGuess::class, $result);

        $options = $result->getOptions();
        self::assertEquals($customTemplate, $options['template']);
    }

    public function testGuessSorterWithValidPaymentStatusClass(): void
    {
        $result = $this->guesser->guessSorter(
            PaymentStatus::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertInstanceOf(ColumnGuess::class, $result);
        self::assertEquals(Guess::HIGH_CONFIDENCE, $result->getConfidence());

        $options = $result->getOptions();
        self::assertEquals('entity.paymentStatus', $options['data_name']);
    }

    public function testGuessFilterWithValidPaymentStatusClass(): void
    {
        $result = $this->guesser->guessFilter(
            PaymentStatus::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertInstanceOf(ColumnGuess::class, $result);
        self::assertEquals(Guess::HIGH_CONFIDENCE, $result->getConfidence());

        $options = $result->getOptions();
        self::assertEquals(PaymentStatusFilter::NAME, $options['type']);
        self::assertEquals('entity.paymentStatus', $options['data_name']);
    }

    public function testGuessFormatterWithInvalidClass(): void
    {
        $result = $this->guesser->guessFormatter(
            \stdClass::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessFormatterWithInvalidProperty(): void
    {
        $result = $this->guesser->guessFormatter(
            PaymentStatus::class,
            'invalidProperty',
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessSorterWithInvalidClass(): void
    {
        $result = $this->guesser->guessSorter(
            \stdClass::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessSorterWithInvalidProperty(): void
    {
        $result = $this->guesser->guessSorter(
            PaymentStatus::class,
            'invalidProperty',
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessFilterWithInvalidClass(): void
    {
        $result = $this->guesser->guessFilter(
            \stdClass::class,
            PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME,
            'string'
        );

        self::assertNull($result);
    }

    public function testGuessFilterWithInvalidProperty(): void
    {
        $result = $this->guesser->guessFilter(
            PaymentStatus::class,
            'invalidProperty',
            'string'
        );

        self::assertNull($result);
    }
}
