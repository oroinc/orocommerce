<?php

namespace Oro\Bundle\RFPBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Prevents editing of RFPs when some workflow for this entity is enabled.
 */
class FrontendRequestVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [BasicPermission::EDIT];

    /** @var FrontendHelper */
    protected $frontendHelper;

    /** @var WorkflowManager */
    protected $workflowManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FrontendHelper $frontendHelper,
        WorkflowManager $workflowManager
    ) {
        parent::__construct($doctrineHelper);

        $this->frontendHelper = $frontendHelper;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIdentifier($object)
    {
        // disallow EDIT for all Requests, so id does not matter
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->frontendHelper->isFrontendRequest() && $this->hasActiveWorkflows('b2b_rfq_frontoffice_flow')) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param string $groupName
     * @return boolean
     */
    protected function hasActiveWorkflows($groupName)
    {
        $workflows = $this->workflowManager->getApplicableWorkflows(Request::class);
        foreach ($workflows as $workflow) {
            if (in_array($groupName, $workflow->getDefinition()->getExclusiveRecordGroups(), true)) {
                return true;
            }
        }

        return false;
    }
}
