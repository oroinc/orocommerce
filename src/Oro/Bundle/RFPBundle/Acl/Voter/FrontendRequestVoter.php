<?php

namespace Oro\Bundle\RFPBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class FrontendRequestVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_EDIT = 'EDIT';

    /** @var array */
    protected $supportedAttributes = [
        self::ATTRIBUTE_EDIT,
    ];

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
    public function supportsClass($class)
    {
        return is_a($class, Request::class, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->applicationProvider->getCurrentApplication() === ApplicationProvider::COMMERCE_APPLICATION
                && $this->workflowManager->isActiveWorkflow('rfq_frontoffice_default')) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
