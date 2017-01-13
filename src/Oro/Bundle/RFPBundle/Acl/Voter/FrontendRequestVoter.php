<?php

namespace Oro\Bundle\RFPBundle\Acl\Voter;

use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FrontendRequestVoter extends Voter
{
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
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === 'EDIT' &&
            $subject instanceof Request &&
            $this->applicationProvider->getCurrentApplication() === ApplicationProvider::COMMERCE_APPLICATION;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        return !$this->hasActiveWorkflows('b2b_rfq_frontoffice_flow');
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
