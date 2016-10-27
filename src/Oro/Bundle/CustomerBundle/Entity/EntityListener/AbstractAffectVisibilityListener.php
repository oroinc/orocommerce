<?php

namespace Oro\Bundle\CustomerBundle\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Model\VisibilityMessageHandler;

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
        $this->visibilityMessageHandler->addVisibilityMessageToSchedule($this->topic, $entity);
    }

    /**
     * @param object $entity
     */
    public function preUpdate($entity)
    {
        $this->visibilityMessageHandler->addVisibilityMessageToSchedule($this->topic, $entity);
    }

    /**
     * @param object $entity
     */
    public function preRemove($entity)
    {
        $this->visibilityMessageHandler->addVisibilityMessageToSchedule($this->topic, $entity);
    }
}
