<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

/**
 * Fill Slug URL caches with data received for a given set of entities.
 */
class UrlCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var SluggableUrlDumper
     */
    private $dumper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        SluggableUrlDumper $dumper,
        LoggerInterface $logger
    ) {
        $this->dumper = $dumper;
        $this->logger = $logger;
    }

    public function setJobRunner(JobRunner $jobRunner)
    {
        $this->jobRunner = $jobRunner;
    }

    public function setMessageFactory(MessageFactoryInterface $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $messageData = JSON::decode($message->getBody());
            $entities = $this->messageFactory->getEntitiesFromMessage($messageData);

            $result = $this->jobRunner->runDelayed($messageData['jobId'], function () use ($entities) {
                foreach ($entities as $entity) {
                    $this->dumper->dumpByEntity($entity);
                }

                return true;
            });

            return $result ? self::ACK : self::REJECT;
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                [
                    'message' => $message,
                    'exception' => $e
                ]
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $this->logger->error(
                'Unexpected exception occurred during queue message processing',
                [
                    'topic' => Topics::PROCESS_CALCULATE_URL_CACHE,
                    'exception' => $e
                ]
            );

            return self::REJECT;
        }
    }

    public static function getSubscribedTopics()
    {
        return [Topics::PROCESS_CALCULATE_URL_CACHE];
    }
}
