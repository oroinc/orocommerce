<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Workflow\Operation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository;
use Oro\Bundle\SaleBundle\Manager\QuoteDemandManager;
use Oro\Bundle\SaleBundle\Workflow\Operation\GuestQuoteSubmitToOrder;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GuestQuoteSubmitToOrderTest extends TestCase
{
    use EntityTrait;

    private OperationServiceInterface|MockObject $baseQuoteSubmitToOrder;
    private FeatureChecker|MockObject $featureChecker;
    private ManagerRegistry|MockObject $registry;
    private QuoteDemandManager|MockObject $quoteDemandManager;
    private TokenStorageInterface|MockObject $tokenStorage;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private GuestQuoteSubmitToOrder $operation;

    protected function setUp(): void
    {
        $this->baseQuoteSubmitToOrder = $this->createMock(OperationServiceInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->quoteDemandManager = $this->createMock(QuoteDemandManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->operation = new GuestQuoteSubmitToOrder(
            $this->baseQuoteSubmitToOrder,
            $this->featureChecker,
            $this->registry,
            $this->quoteDemandManager,
            $this->tokenStorage,
            $this->urlGenerator
        );
    }

    public function testIsPreConditionAllowedWhenBaseServicePreConditionFails(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();

        $this->baseQuoteSubmitToOrder->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(false);

        $this->assertFalse($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testIsPreConditionAllowedWhenTokenIsNotAnonymousCustomerUserToken(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();
        $token = $this->createMock(TokenInterface::class);

        $this->baseQuoteSubmitToOrder->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertTrue($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testIsPreConditionAllowedWhenTokenIsAnonymousCustomerUserTokenAndFeatureDisabled(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->baseQuoteSubmitToOrder->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_checkout')
            ->willReturn(false);

        $this->assertFalse($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testIsPreConditionAllowedWhenTokenIsAnonymousCustomerUserTokenAndFeatureEnabled(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->baseQuoteSubmitToOrder->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($data, $errors)
            ->willReturn(true);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_checkout')
            ->willReturn(true);

        $this->assertTrue($this->operation->isPreConditionAllowed($data, $errors));
    }

    public function testExecuteWhenTokenIsNotAnonymousCustomerUserToken(): void
    {
        $data = new ActionData();
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->baseQuoteSubmitToOrder->expects($this->once())
            ->method('execute')
            ->with($data);

        $this->operation->execute($data);
    }

    public function testExecuteWhenTokenIsAnonymousCustomerUserTokenAndQuoteDemandExists(): void
    {
        $data = new ActionData();
        $quote = $this->getEntity(Quote::class, ['id' => 1]);
        $visitor = $this->createMock(CustomerVisitor::class);
        $quoteDemand = $this->getEntity(QuoteDemand::class, ['id' => 2]);
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $data->offsetSet('data', $quote);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);

        $quoteDemandRepository = $this->createMock(QuoteDemandRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(QuoteDemand::class)
            ->willReturn($quoteDemandRepository);

        $quoteDemandRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['quote' => $quote, 'visitor' => $visitor])
            ->willReturn($quoteDemand);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_sale_quote_frontend_choice', ['id' => $quoteDemand->getId()])
            ->willReturn('generated_url');

        $this->operation->execute($data);
        $this->assertEquals('generated_url', $data->offsetGet('redirectUrl'));
    }

    public function testExecuteWhenTokenIsAnonymousCustomerUserTokenAndQuoteDemandDoesNotExist(): void
    {
        $data = new ActionData();
        $quote = $this->getEntity(Quote::class, ['id' => 1]);
        $visitor = $this->createMock(CustomerVisitor::class);
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $data->offsetSet('data', $quote);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);

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
            ->with(['quote' => $quote, 'visitor' => $visitor])
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
