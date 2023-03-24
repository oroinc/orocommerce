<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Handler;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Handler\RequestHandler;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProvider;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var RequestHandler */
    private $requestHandler;

    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->requestHandler = new RequestHandler(
            $this->totalProvider,
            $this->eventDispatcher,
            $this->authorizationChecker,
            $this->entityRoutingHelper,
            $this->doctrine
        );
    }

    /**
     * @dataProvider getRecalculateTotalProvider
     */
    public function testRecalculateTotalsWithoutEntityID(string $originalClassName, int $entityId)
    {
        $correctEntityClass = str_replace('_', '\\', $originalClassName);

        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->with($originalClassName)
            ->willReturn($correctEntityClass);

        $entity = new $correctEntityClass();
        if ($entityId > 0) {
            $repository = $this->createMock(EntityRepository::class);
            $repository->expects($this->once())
                ->method('find')
                ->willReturn($entity);
            $this->doctrine->expects($this->once())
                ->method('getRepository')
                ->willReturn($repository);
            $this->authorizationChecker->expects($this->once())
                ->method('isGranted')
                ->willReturn(true);
        }

        $request = $this->createMock(Request::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn(new TotalCalculateBeforeEvent($entity, $request));

        $this->totalProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturnSelf();
        $this->totalProvider->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($entity)
            ->willReturn($this->getExpectedTotal());

        $totals = $this->requestHandler->recalculateTotals($originalClassName, $entityId, $request);
        $expectedTotals = $this->getExpectedTotal();

        self::assertEquals($expectedTotals, $totals);
    }

    public function getRecalculateTotalProvider(): array
    {
        return [
            'test with entityId = 0' => [
                'originalClassName' => EntityStub::class,
                'entityId' => 0
            ],
            'test with URL safe class and entityId = 0' => [
                'originalClassName' => 'Oro_Bundle_PricingBundle_Tests_Unit_SubtotalProcessor_Stub_EntityStub',
                'entityId' => 0
            ],
            'test with entityId > 0' => [
                'originalClassName' => EntityStub::class,
                'entityId' => 1
            ],
            'test with URL safe class and entityId > 0' => [
                'originalClassName' => 'Oro_Bundle_PricingBundle_Tests_Unit_SubtotalProcessor_Stub_EntityStub',
                'entityId' => 1
            ],
        ];
    }

    public function testRecalculateTotalsNoAccessView()
    {
        $this->expectException(AccessDeniedException::class);

        $entityClassName = EntityStub::class;
        $entityId = 1;

        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->willReturn($entityClassName);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(new $entityClassName());
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->requestHandler->recalculateTotals($entityClassName, $entityId);
    }

    private function getExpectedTotal(): array
    {
        return [
            'total' => [
                'type' => 'Total',
                'label' => 'Total',
                'amount' => 100,
                'currency' => 'USD',
                'visible' => true,
                'data' => null
            ],
            'subtotals' => [
                [
                    'type' => 'Shipping Cost',
                    'label' => 'Shipping Cost',
                    'amount' => 100,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => null
                ]

            ]
        ];
    }
}
