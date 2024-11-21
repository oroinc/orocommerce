<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Workflow\Operation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;
use Oro\Bundle\SaleBundle\Workflow\Operation\QuoteSubmitToOrder;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class QuoteSubmitToOrderTest extends TestCase
{
    use EntityTrait;

    private WorkflowManager|MockObject $workflowManager;
    private ActionExecutor|MockObject $actionExecutor;
    private ManagerRegistry|MockObject $registry;
    private QuoteDemandManager|MockObject $quoteDemandManager;
    private TokenStorageInterface|MockObject $tokenStorage;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private QuoteSubmitToOrder $operation;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->quoteDemandManager = $this->createMock(QuoteDemandManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->operation = new QuoteSubmitToOrder(
            $this->workflowManager,
            $this->actionExecutor,
            $this->registry,
            $this->quoteDemandManager,
            $this->tokenStorage,
            $this->urlGenerator
        );
    }

    public function testIsPreConditionAllowedWhenQuoteNotAcceptable(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('quote_acceptable', [$data->getEntity()], $errors)
            ->willReturn(false);

        $this->workflowManager->expects($this->never())
            ->method('getAvailableWorkflowByRecordGroup');

        $this->assertFalse($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testIsPreConditionAllowedWhenNoWorkflow(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('quote_acceptable', [$data->getEntity()], $errors)
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn(null);

        $this->assertFalse($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testIsPreConditionAllowedWhenAllConditionsPass(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('quote_acceptable', [$data->getEntity()], $errors)
            ->willReturn(true);

        $workflow = $this->createMock(Workflow::class);
        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->assertTrue($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testExecuteWhenQuoteDemandExists(): void
    {
        $data = new ActionData();
        $quote = $this->getEntity(Quote::class, ['id' => 1]);
        $currentUser = $this->createMock(CustomerUser::class);
        $quoteDemand = $this->getEntity(QuoteDemand::class, ['id' => 2]);
        $token = $this->createMock(TokenInterface::class);

        $data->offsetSet('data', $quote);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        $quoteDemandRepository = $this->createMock(QuoteDemandRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(QuoteDemand::class)
            ->willReturn($quoteDemandRepository);

        $quoteDemandRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['quote' => $quote, 'customerUser' => $currentUser])
            ->willReturn($quoteDemand);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_frontend_choice', ['id' => $quoteDemand->getId()])
            ->willReturn('generated_url');

        $this->operation->execute($data);
        $this->assertEquals('generated_url', $data->offsetGet('redirectUrl'));
    }

    public function testExecuteWhenQuoteDemandDoesNotExist(): void
    {
        $data = new ActionData();
        $quote = $this->getEntity(Quote::class, ['id' => 1]);
        $currentUser = $this->createMock(CustomerUser::class);
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $token = $this->createMock(TokenInterface::class);

        $data->offsetSet('data', $quote);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        $quoteDemandRepository = $this->createMock(QuoteDemandRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(QuoteDemand::class)
            ->willReturn($quoteDemandRepository);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(QuoteDemand::class));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->isInstanceOf(QuoteDemand::class));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(QuoteDemand::class)
            ->willReturn($em);

        $quoteDemandRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['quote' => $quote, 'customerUser' => $currentUser])
            ->willReturn(null);

        $this->quoteDemandManager->expects($this->once())
            ->method('recalculateSubtotals')
            ->with($this->isInstanceOf(QuoteDemand::class));

        $this->quoteDemandManager->expects($this->once())
            ->method('updateQuoteProductDemandChecksum')
            ->with($this->isInstanceOf(QuoteDemand::class));

        $this->operation->execute($data);
    }
}
