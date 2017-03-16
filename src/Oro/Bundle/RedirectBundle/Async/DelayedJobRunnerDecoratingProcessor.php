<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class DelayedJobRunnerDecoratingProcessor implements MessageProcessorInterface
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var MessageProcessorInterface
     */
    private $processor;

    /**
     * @param JobRunner $jobRunner
     * @param MessageProcessorInterface $processor
     */
    public function __construct(JobRunner $jobRunner, MessageProcessorInterface $processor)
    {
        $this->jobRunner = $jobRunner;
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());
        if (!array_key_exists('jobId', $data)) {
            return self::REJECT;
        }

        return $this->jobRunner->runDelayed($data['jobId'], function () use ($data, $message, $session) {
            $processorMessage = clone $message;
            unset($data['jobId']);
            $processorMessage->setBody(JSON::encode($data));

            return $this->processor->process($processorMessage, $session);
        });
    }
}
