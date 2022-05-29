<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Handler\RequestHandler;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Stub\EntityStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TotalProcessorProvider */
    private $totalProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRoutingHelper */
    private $entityRoutingHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestHandler */
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
            $em = $this->expectsFindEntity($entity);
            $this->doctrine->expects($this->once())
                ->method('getManager')
                ->willReturn($em);
            $this->authorizationChecker->expects($this->once())
                ->method('isGranted')
                ->willReturn(true);
        }

        $request = $this->createMock(Request::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn(new TotalCalculateBeforeEvent($entity, $request));

        $this->prepareTotal();

        $this->totalProvider->expects($this->once())
            ->method('enableRecalculation');

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
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $entityClassName = EntityStub::class;
        $entityId = 1;

        $this->entityRoutingHelper->expects($this->once())
            ->method('resolveEntityClass')
            ->willReturn($entityClassName);

        $em = $this->expectsFindEntity(new $entityClassName());
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->willReturn($em);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->requestHandler->recalculateTotals($entityClassName, $entityId);
    }

    private function prepareTotal(): void
    {
        $this->totalProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturn($this->totalProvider);
        $this->totalProvider->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->willReturn($this->getExpectedTotal());
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

    private function expectsFindEntity(object $returnEntity): EntityManagerInterface
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn($returnEntity);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        return $manager;
    }
}
