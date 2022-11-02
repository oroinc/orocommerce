<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\SearchProcessingEngineExceptionListener;

class SearchProcessingEngineExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    private SearchProcessingEngineExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new SearchProcessingEngineExceptionListener();
    }

    public function testUnsupportedExceptions(): void
    {
        $exception = new \Exception();
        $event = new SearchProcessingEngineExceptionEvent($exception);
        $this->listener->process($event);

        $this->assertFalse($event->isRetryable());
    }

    /**
     * @dataProvider supportedExceptions
     */
    public function testSupportedExceptions(string $exception): void
    {
        $exception = $this->createMock($exception);
        $event = new SearchProcessingEngineExceptionEvent($exception);
        $this->listener->process($event);

        $this->assertTrue($event->isRetryable());
    }

    /**
     * @return array[]
     */
    public function supportedExceptions(): array
    {
        return [
            'RetryableException' => [
                'exception' => RetryableException::class,
            ],
            'UniqueConstraintViolationException' => [
                'exception' => UniqueConstraintViolationException::class,
            ],
            'ForeignKeyConstraintViolationException' => [
                'exception' => ForeignKeyConstraintViolationException::class,
            ],
        ];
    }
}
