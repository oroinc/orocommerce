<?php

namespace Oro\Bundle\AccountBundle\Model;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class VisibilityTriggerHandler
{
    /**
     * @var VisibilityTriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;
    
    /**
     * @var array
     */
    protected $scheduledTriggers = [];

    /**
     * @param VisibilityTriggerFactory $triggerFactory
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        VisibilityTriggerFactory $triggerFactory,
        MessageProducerInterface $messageProducer
    ) {
        $this->triggerFactory = $triggerFactory;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param string $topic
     * @param VisibilityInterface $productVisibility
     */
    public function addTriggersForProductVisibility($topic, VisibilityInterface $productVisibility)
    {
        $trigger = $this->triggerFactory->createTrigger($productVisibility);

        if (!$this->isScheduledTrigger($topic, $trigger)) {
            $this->scheduleTrigger($topic, $trigger);
        }
    }

    public function sendScheduledTriggers()
    {
        foreach ($this->scheduledTriggers as $topic => $triggers) {
            if (count($triggers) > 0) {
                foreach ($triggers as $trigger) {
                    $this->messageProducer->send($topic, $trigger);
                }
            }
        }

        $this->scheduledTriggers = [];
    }

    /**
     * @param string $topic
     * @param array $trigger
     * @return bool
     */
    protected function isScheduledTrigger($topic, array $trigger)
    {
        $triggers = empty($this->scheduledTriggers[$topic]) ? [] : $this->scheduledTriggers[$topic];

        return array_key_exists($this->getTriggerKey($trigger), $triggers);
    }

    /**
     * @param string $topic
     * @param array $trigger
     */
    protected function scheduleTrigger($topic, array $trigger)
    {
        $this->scheduledTriggers[$topic][$this->getTriggerKey($trigger)] = $trigger;
    }

    /**
     * @param $trigger
     * @return string
     */
    protected function getTriggerKey($trigger)
    {
        return $trigger[VisibilityTriggerFactory::VISIBILITY_CLASS] . PATH_SEPARATOR
            . $trigger[VisibilityTriggerFactory::ID];
    }
}
