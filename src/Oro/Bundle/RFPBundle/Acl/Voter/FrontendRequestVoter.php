<?php

namespace Oro\Bundle\RFPBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
     * @param ApplicationProvider $applicationProvider
     * @param WorkflowManager $workflowManager
     */
    public function __construct(ApplicationProvider $applicationProvider, WorkflowManager $workflowManager)
    {
        $this->applicationProvider = $applicationProvider;
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     *
     * @deprecated Will be removed in 2.0 version
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === 'EDIT' &&
            $subject instanceof Request &&
            $this->applicationProvider->getCurrentApplication() === ApplicationProvider::COMMERCE_APPLICATION;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     *
     * @deprecated Will be removed in 2.0 version
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        return !$this->hasActiveWorkflows('b2b_rfq_frontoffice_flow');
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
