<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
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
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param VisibilityMessageHandler $visibilityMessageHandler
     * @param ScopeManager $scopeManager
     */
    public function __construct(VisibilityMessageHandler $visibilityMessageHandler, ScopeManager $scopeManager)
    {
        $this->visibilityMessageHandler = $visibilityMessageHandler;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = (string)$topic;
    }
    
    /**
     * @param object|VisibilityInterface $entity
     */
    public function prePersist($entity)
    {
        //TODO: remove after form will work
        $scopeType = 'product_visibility';
        if ($entity instanceof AccountProductVisibility) {
            $scopeType = 'account_product_visibility';
        }
        if ($entity instanceof AccountGroupProductVisibility) {
            $scopeType = 'account_group_product_visibility';
        }
        $entity->setScope($this->scopeManager->findOrCreate($scopeType, $entity));
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
        //TODO: remove after form will work BB 4506
        $scopeType = 'product_visibility';
        if ($entity instanceof AccountProductVisibility) {
            $scopeType = 'account_product_visibility';
        }
        if ($entity instanceof AccountGroupProductVisibility) {
            $scopeType = 'account_group_product_visibility';
        }
        $entity->setScope($this->scopeManager->findOrCreate($scopeType, $entity));
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
