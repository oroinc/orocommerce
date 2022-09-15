<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async;

use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexGranulizedTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Makes reindex of the specified entities by classes and ids with optional granulating.
 */
class WebsiteSearchReindexProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use ContextTrait;
    use LoggerAwareTrait;
    use WebsiteSearchEngineExceptionAwareProcessorTrait;

    private MessageProcessorInterface $delayedJobRunnerProcessor;

    private WebsiteSearchReindexGranulizedProcessor $websiteSearchReindexGranulizedProcessor;

    private ReindexMessageGranularizer $reindexMessageGranularizer;

    private MessageProducerInterface $messageProducer;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        MessageProcessorInterface $delayedJobRunnerProcessor,
        WebsiteSearchReindexGranulizedProcessor $websiteSearchReindexGranulizedProcessor,
        ReindexMessageGranularizer $reindexMessageGranularizer,
        MessageProducerInterface $messageProducer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->delayedJobRunnerProcessor = $delayedJobRunnerProcessor;
        $this->websiteSearchReindexGranulizedProcessor = $websiteSearchReindexGranulizedProcessor;
        $this->reindexMessageGranularizer = $reindexMessageGranularizer;
        $this->messageProducer = $messageProducer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedTopics(): array
    {
        return [WebsiteSearchReindexTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();
        if (isset($messageBody['jobId'])) {
            return $this->delayedJobRunnerProcessor->process($message, $session);
        }

        return $this->doProcess(
            function () use ($messageBody) {
                $this->dispatchReindexEvent($messageBody['class'], $messageBody['context']);

                if ($messageBody['granulize']) {
                    return $this->produceChildMessages($messageBody['class'], $messageBody['context']);
                }

                return $this->websiteSearchReindexGranulizedProcessor->doReindex(
                    $messageBody['class'],
                    array_merge($messageBody['context'], ['skip_pre_processing' => true]),
                );
            },
            $this->eventDispatcher,
            $this->logger
        );
    }

    /**
     * @return string Message status
     */
    private function produceChildMessages(array|string $class, array $context): string
    {
        $childMessages = $this->reindexMessageGranularizer
            ->process($class, $this->getContextWebsiteIds($context), $context);

        $firstMessageBody = [];
        foreach ($childMessages as $childMessageBody) {
            if ($firstMessageBody === []) {
                // Adds the first message body to a buffer to check if it is the only one - to process instantly.
                $firstMessageBody = $childMessageBody;
                continue;
            }

            if ($firstMessageBody) {
                // Sends the first message body to MQ as there is definitely the second one
                // because we reached 2nd iteration.
                $this->messageProducer->send(WebsiteSearchReindexGranulizedTopic::getName(), $firstMessageBody);
                // Clears a buffer as we don't need it anymore.
                $firstMessageBody = null;
            }

            $this->messageProducer->send(WebsiteSearchReindexGranulizedTopic::getName(), $childMessageBody);
        }

        if ($firstMessageBody) {
            // Processes first message body instantly as it is happened to be the only one to process.
            return $this->websiteSearchReindexGranulizedProcessor->doReindex(
                $firstMessageBody['class'],
                array_merge($firstMessageBody['context'], ['skip_pre_processing' => true])
            );
        }

        return self::ACK;
    }

    private function dispatchReindexEvent(array|string $class, array $context): void
    {
        $event = new BeforeReindexEvent($class, $context);
        $this->eventDispatcher->dispatch($event, BeforeReindexEvent::EVENT_NAME);
    }
}
