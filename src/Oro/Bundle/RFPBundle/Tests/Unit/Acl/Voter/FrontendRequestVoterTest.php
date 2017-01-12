<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Acl\Voter\FrontendRequestVoter;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class FrontendRequestVoterTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ApplicationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationProvider;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $token;

    /** @var FrontendRequestVoter */
    protected $voter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->applicationProvider = $this->getMockBuilder(ApplicationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->token = $this->createMock(TokenInterface::class);

        $this->doctrineHelper->expects($this->any())->method('getEntityClass')->willReturn(Request::class);
        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')->willReturn(1);

        $this->voter = new FrontendRequestVoter(
            $this->doctrineHelper,
            $this->applicationProvider,
            $this->workflowManager
        );
    }

    public function testSupportsClass()
    {
        $this->assertTrue($this->voter->supportsClass(Request::class));
    }

    public function testSupportsClassWithUnsupportedClass()
    {
        $this->assertFalse($this->voter->supportsClass('stdClass'));
    }

    public function testSupportsAttribute()
    {
        $this->assertTrue($this->voter->supportsAttribute('EDIT'));
    }

    public function testSupportsAttributeWithUnsupportedAttribute()
    {
        $this->assertFalse($this->voter->supportsAttribute('VIEW'));
    }

    public function testVoteWithActiveFrontoffice()
    {
        $definition = new WorkflowDefinition();
        $definition->setExclusiveRecordGroups(['b2b_rfq_frontoffice_flow']);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->once())->method('getDefinition')->willReturn($definition);

        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')->willReturn(ApplicationProvider::COMMERCE_APPLICATION);

        $this->workflowManager->expects($this->once())->method('getApplicableWorkflows')
            ->with(Request::class)->willReturn([$workflow]);

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_DENIED,
            $this->voter->vote($this->token, new Request(), [FrontendRequestVoter::ATTRIBUTE_EDIT])
        );
    }

    public function testVoteWithInactiveFrontoffice()
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')->willReturn(ApplicationProvider::COMMERCE_APPLICATION);

        $this->workflowManager->expects($this->once())->method('getApplicableWorkflows')
            ->with(Request::class)->willReturn([]);

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), [FrontendRequestVoter::ATTRIBUTE_EDIT])
        );
    }

    public function testVoteWithUnknownApplication()
    {
        $this->applicationProvider->expects($this->once())
            ->method('getCurrentApplication')->willReturn('unknown_application');

        $this->workflowManager->expects($this->never())->method('getApplicableWorkflows');

        $this->assertEquals(
            FrontendRequestVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new Request(), [FrontendRequestVoter::ATTRIBUTE_EDIT])
        );
    }
}
