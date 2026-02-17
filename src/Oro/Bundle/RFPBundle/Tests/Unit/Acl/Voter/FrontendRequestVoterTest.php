<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RFPBundle\Acl\Voter\FrontendRequestVoter;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FrontendRequestVoterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private FrontendHelper&MockObject $frontendHelper;
    private WorkflowManager&MockObject $workflowManager;
    private TokenInterface&MockObject $token;
    private FrontendRequestVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->token = $this->createMock(TokenInterface::class);

        $container = TestContainerBuilder::create()
            ->add(WorkflowManager::class, $this->workflowManager)
            ->getContainer($this);

        $this->voter = new FrontendRequestVoter($this->doctrineHelper, $this->frontendHelper, $container);
        $this->voter->setClassName(Request::class);
    }

    public function testVoteForFrontend(): void
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups(['b2b_rfq_frontoffice_flow']);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->workflowManager->expects(self::once())
            ->method('getApplicableWorkflows')
            ->with(Request::class)
            ->willReturn([$workflow]);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, new Request(), ['EDIT'])
        );
    }

    public function testVoteForFrontendAndWithoutApplicableWorkflows(): void
    {
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->workflowManager->expects(self::once())
            ->method('getApplicableWorkflows')
            ->with(Request::class)
            ->willReturn([]);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), ['EDIT'])
        );
    }

    public function testVoteForBackend(): void
    {
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->workflowManager->expects(self::never())
            ->method('getApplicableWorkflows');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), ['EDIT'])
        );
    }

    public function testVoteWithUnsupportedAttribute(): void
    {
        $this->frontendHelper->expects(self::never())
            ->method('isFrontendRequest');

        $this->workflowManager->expects(self::never())
            ->method('getApplicableWorkflows');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), ['VIEW'])
        );
    }

    public function testVoteWithUnsupportedClass(): void
    {
        $this->frontendHelper->expects(self::never())
            ->method('isFrontendRequest');

        $this->workflowManager->expects(self::never())
            ->method('getApplicableWorkflows');

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), ['EDIT'])
        );
    }
}
