<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\SearchProcessingEngineExceptionListener;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class SearchProcessingEngineExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchProcessingEngineExceptionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new SearchProcessingEngineExceptionListener();
    }

    public function testUnsupportedExceptions(): void
    {
        $exception = new \Exception();
        $event = new SearchProcessingEngineExceptionEvent($exception);
        $this->listener->process($event);

        $this->assertNull($event->getConsumptionResult());
    }

    /**
     * @dataProvider supportedExceptions
     */
    public function testSupportedExceptions(string $exception, string $expected): void
    {
        $exception = $this->createMock($exception);
        $event = new SearchProcessingEngineExceptionEvent($exception);
        $this->listener->process($event);

        $this->assertEquals($expected, $event->getConsumptionResult());
    }

    /**
     * @return array[]
     */
    public function supportedExceptions(): array
    {
        return [
            'RetryableException' => [
                'exception' => RetryableException::class,
                'expected' => MessageProcessorInterface::REQUEUE
            ],
            'UniqueConstraintViolationException' => [
                'exception' => UniqueConstraintViolationException::class,
                'expected' => MessageProcessorInterface::REQUEUE
            ],
            'ForeignKeyConstraintViolationException' => [
                'exception' => ForeignKeyConstraintViolationException::class,
                'expected' => MessageProcessorInterface::REQUEUE
            ],
        ];
    }
}
