<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RFPBundle\Acl\Voter\FrontendRequestVoter;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendRequestVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $frontendHelper;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $token;

    /** @var FrontendRequestVoter */
    protected $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new FrontendRequestVoter(
            $this->doctrineHelper,
            $this->frontendHelper,
            $this->workflowManager
        );
        $this->voter->setClassName(Request::class);
    }

    public function testVoteForFrontend()
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups(['b2b_rfq_frontoffice_flow']);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with(Request::class)
            ->willReturn([$workflow]);

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_DENIED,
            $this->voter->vote($this->token, new Request(), ['EDIT'])
        );
    }

    public function testVoteForFrontendAndWithoutApplicableWorkflows()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with(Request::class)
            ->willReturn([]);

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), ['EDIT'])
        );
    }

    public function testVoteForBackend()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->workflowManager->expects($this->never())
            ->method('getApplicableWorkflows');

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), ['EDIT'])
        );
    }

    public function testVoteWithUnsupportedAttribute()
    {
        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->workflowManager->expects($this->never())
            ->method('getApplicableWorkflows');

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), ['VIEW'])
        );
    }

    public function testVoteWithUnsupportedClass()
    {
        $this->frontendHelper->expects($this->never())
            ->method('isFrontendRequest');

        $this->workflowManager->expects($this->never())
            ->method('getApplicableWorkflows');

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), ['EDIT'])
        );
    }
}
