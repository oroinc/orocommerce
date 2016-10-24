<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;

abstract class AbstractAffectVisibilityListener
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
     * @param VisibilityMessageHandler $visibilityMessageHandler
     */
    public function __construct(VisibilityMessageHandler $visibilityMessageHandler)
    {
        $this->visibilityMessageHandler = $visibilityMessageHandler;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = (string)$topic;
    }

    /**
     * @param object $entity
     */
    public function postPersist($entity)
    {
        $this->visibilityMessageHandler->addMessageToSchedule($this->topic, $entity);
    }

    /**
     * @param object $entity
     */
    public function preUpdate($entity)
    {
        $this->visibilityMessageHandler->addMessageToSchedule($this->topic, $entity);
    }

    /**
     * @param object $entity
     */
    public function preRemove($entity)
    {
        $this->visibilityMessageHandler->addMessageToSchedule($this->topic, $entity);
    }
}
