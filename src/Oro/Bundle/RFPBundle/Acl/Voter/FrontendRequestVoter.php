<?php

namespace Oro\Bundle\RFPBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents editing of RFPs when some workflow for this entity is enabled.
 */
class FrontendRequestVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::EDIT];

    private FrontendHelper $frontendHelper;
    private ContainerInterface $container;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FrontendHelper $frontendHelper,
        ContainerInterface $container
    ) {
        parent::__construct($doctrineHelper);
        $this->frontendHelper = $frontendHelper;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_workflow.manager' => WorkflowManager::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityIdentifier($object)
    {
        // disallow EDIT for all Requests, so id does not matter
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->frontendHelper->isFrontendRequest() && $this->hasActiveWorkflows('b2b_rfq_frontoffice_flow')) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function hasActiveWorkflows(string $groupName): bool
    {
        $workflows = $this->getWorkflowManager()->getApplicableWorkflows(Request::class);
        foreach ($workflows as $workflow) {
            if (\in_array($groupName, $workflow->getDefinition()->getExclusiveRecordGroups(), true)) {
                return true;
            }
        }

        return false;
    }

    private function getWorkflowManager(): WorkflowManager
    {
        return $this->container->get('oro_workflow.manager');
    }
}
