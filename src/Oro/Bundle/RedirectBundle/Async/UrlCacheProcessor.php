<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UrlCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
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

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $data = $this->getResolvedMessageData(JSON::decode($message->getBody()));
            $this->dumper->dump($data['route_name'], $data['entity_ids']);

            return self::ACK;
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'Queue Message is invalid',
                ['exception' => $e]
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

    /**
     * @param array $message
     * @return array
     */
    private function getResolvedMessageData(array $message)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired(['route_name', 'entity_ids']);

        $optionsResolver->setAllowedTypes('route_name', 'string');
        $optionsResolver->setAllowedTypes('entity_ids', 'array');

        return $optionsResolver->resolve($message);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PROCESS_CALCULATE_URL_CACHE];
    }
}
