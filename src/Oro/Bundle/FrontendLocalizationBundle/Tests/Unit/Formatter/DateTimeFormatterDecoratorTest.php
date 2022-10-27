<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Formatter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendLocalizationBundle\Formatter\DateTimeFormatterDecorator;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DateTimeFormatterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private const US_LOCALE = 'en_US';

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var DateTimeFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formatter;

    /**
     * @var DateTimeFormatterDecorator
     */
    private $formatterDecorator;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatter::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->formatterDecorator = new DateTimeFormatterDecorator($this->formatter, $this->frontendHelper);
    }

    public function testGetDatePatternFrontendRequest(): void
    {
        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('getPattern')
            ->with(\IntlDateFormatter::SHORT, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn('M/d/yy, h:mm a');

        $this->assertEquals(
            'M/d/yyyy, h:mm a',
            $this->formatterDecorator
                ->getPattern(null, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testGetDatePattern(): void
    {
        $expected = "MMMM d, y 'at' h:mm:ss a z";

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('getPattern')
            ->with(\IntlDateFormatter::LONG, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator
                ->getPattern(\IntlDateFormatter::LONG, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormatFrontendRequest(): void
    {
        $date = new \DateTime('2020-05-05 00:00:00');
        $expected = '5/5/20, 12:00:00 AM GMT+2';

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->format($date, null, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormat(): void
    {
        $date = new \DateTime('2020-05-05 00:00:00');
        $expected = 'May 5, 2020 at 12:00:00 AM GMT+2';

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, \IntlDateFormatter::LONG, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->format(
                $date,
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::LONG,
                self::US_LOCALE
            )
        );
    }

    public function testFormatDateFrontendRequest(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = '5/5/20';

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('formatDate')
            ->with($date, \IntlDateFormatter::SHORT, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatDate($date, null, self::US_LOCALE)
        );
    }

    public function testFormatDate(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = 'May 5, 2020';

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('formatDate')
            ->with($date, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatDate($date, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormatYearFrontendRequest(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "20";

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('formatYear')
            ->with($date, \IntlDateFormatter::SHORT, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatYear($date, null, self::US_LOCALE)
        );
    }

    public function testFormatYear(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "2020";

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('formatYear')
            ->with($date, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatYear($date, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormatQuarterFrontendRequest(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "Q2/20";

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('formatQuarter')
            ->with($date, \IntlDateFormatter::SHORT, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatQuarter($date, null, self::US_LOCALE)
        );
    }

    public function testFormatQuarter(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "Q2/2020";

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('formatQuarter')
            ->with($date, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatQuarter($date, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormatMonthFrontendRequest(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "5/2020";

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('formatMonth')
            ->with($date, \IntlDateFormatter::SHORT, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatMonth($date, null, self::US_LOCALE)
        );
    }

    public function testFormatMonth(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "May 2020";

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('formatMonth')
            ->with($date, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatMonth($date, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormatDayFrontendRequest(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "05";

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->formatter->expects($this->once())
            ->method('formatDay')
            ->with($date, \IntlDateFormatter::SHORT, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatDay($date, null, self::US_LOCALE)
        );
    }

    public function testFormatDay(): void
    {
        $date = new \DateTime('2020-05-05');
        $expected = "05";

        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->formatter->expects($this->once())
            ->method('formatDay')
            ->with($date, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatDay($date, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testFormatTime(): void
    {
        $date = new \DateTime('2020-05-05 00:15:30');
        $expected = "00:15:30";

        $this->formatter->expects($this->once())
            ->method('formatTime')
            ->with($date, \IntlDateFormatter::LONG, self::US_LOCALE)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->formatterDecorator->formatTime($date, \IntlDateFormatter::LONG, self::US_LOCALE)
        );
    }

    public function testGetDateTime(): void
    {
        $date = '05 May 2020';
        $expected = new \DateTime('2020-05-05');

        $this->formatter->expects($this->once())
            ->method('getDateTime')
            ->with($date)
            ->willReturn($expected);

        $this->assertEquals($expected, $this->formatterDecorator->getDateTime($date));
    }
}
