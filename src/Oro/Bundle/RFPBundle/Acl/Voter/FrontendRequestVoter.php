<?php

namespace Oro\Bundle\RFPBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Restricts EDIT permission for RFP when some workflow for this entity is enabled
 */
class FrontendRequestVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = ['EDIT'];

    /** @var ApplicationProvider */
    protected $applicationProvider;

    /** @var WorkflowManager */
    protected $workflowManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ApplicationProvider $applicationProvider
     * @param WorkflowManager $workflowManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ApplicationProvider $applicationProvider,
        WorkflowManager $workflowManager
    ) {
        parent::__construct($doctrineHelper);

        $this->applicationProvider = $applicationProvider;
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIdentifier($object)
    {
        // disallow EDIT for all Requests, so id does not matter
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->applicationProvider->getCurrentApplication() === ApplicationProvider::COMMERCE_APPLICATION &&
            $this->hasActiveWorkflows('b2b_rfq_frontoffice_flow')
        ) {
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
