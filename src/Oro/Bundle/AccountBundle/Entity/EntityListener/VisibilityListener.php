<?php

namespace Oro\Bundle\AccountBundle\Entity\EntityListener;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Model\VisibilityMessageHandler;

class VisibilityListener
{
    /**
     * @var string
     */
    protected $topic = '';
    
    /**
     * @var VisibilityMessageHandler
     */
    protected $visibilityMessageHandler;

    /**
     * @param VisibilityMessageHandler $productVisibilityChangeTriggerHandler
     * @param string $topic
     */
    public function __construct(VisibilityMessageHandler $productVisibilityChangeTriggerHandler, $topic)
    {
        $this->visibilityMessageHandler = $productVisibilityChangeTriggerHandler;
        $this->topic = (string)$topic;
    }
    
    /**
     * Recalculate visibilities on product visibility change.
     *
     * @param VisibilityInterface $productVisibility
     */
    public function postPersist(VisibilityInterface $productVisibility)
    {
        $this->visibilityMessageHandler->addVisibilityMessageToSchedule(
            $this->topic,
            $productVisibility
        );
    }

    /**
     * Recalculate visibilities on product visibility change.
     *
     * @param VisibilityInterface $productVisibility
     */
    public function preUpdate(VisibilityInterface $productVisibility)
    {
        $this->visibilityMessageHandler->addVisibilityMessageToSchedule(
            $this->topic,
            $productVisibility
        );
    }

    /**
     * Recalculate visibilities on product visibility remove.
     *
     * @param VisibilityInterface $productVisibility
     */
    public function preRemove(VisibilityInterface $productVisibility)
    {
        $this->visibilityMessageHandler->addVisibilityMessageToSchedule(
            $this->topic,
            $productVisibility
        );
    }
}
