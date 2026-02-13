<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DependentPriceListProviderTest extends TestCase
{
    use EntityTrait;

    private PriceRuleLexemeTriggerHandler|MockObject $priceRuleLexemeTriggerHandler;
    private ManagerRegistry|MockObject $doctrine;
    private CacheInterface|MockObject $cache;
    private DependentPriceListProvider $dependentPriceListProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->priceRuleLexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->dependentPriceListProvider = new DependentPriceListProvider($this->priceRuleLexemeTriggerHandler);
        $this->dependentPriceListProvider->setManagerRegistry($this->doctrine);
        $this->dependentPriceListProvider->setCache($this->cache);
    }

    public function testGetDependentPriceLists()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($dependentPriceList1 = $this->getEntity(PriceList::class, ['id' => 2]));
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($dependentPriceList2 = $this->getEntity(PriceList::class, ['id' => 3]));
        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($dependentPriceList3 = $this->getEntity(PriceList::class, ['id' => 4]));

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(4))
            ->method('findEntityLexemes')
            ->willReturnMap([
                [PriceList::class, [], 1, [$lexeme1, $lexeme2]],
                [PriceList::class, [], 2, [$lexeme3]],
                [PriceList::class, [], 3, []],
                [PriceList::class, [], 4, []],
            ]);

        $this->assertEquals(
            [2 => $dependentPriceList1, 3 => $dependentPriceList2, 4 => $dependentPriceList3],
            $this->dependentPriceListProvider->getDependentPriceLists($priceList)
        );
    }

    public function testGetDirectlyDependentPriceLists()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($dependentPriceList1 = $this->getEntity(PriceList::class, ['id' => 2]));
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($dependentPriceList2 = $this->getEntity(PriceList::class, ['id' => 3]));

        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, [], 1)
            ->willReturn(
                [$lexeme1, $lexeme2]
            );

        $this->assertEquals(
            [2 => $dependentPriceList1, 3 => $dependentPriceList2],
            $this->dependentPriceListProvider->getDirectlyDependentPriceLists($priceList)
        );
    }

    public function testAppendDependent()
    {
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($dependentPriceList1 = $this->getEntity(PriceList::class, ['id' => 3]));
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($dependentPriceList2 = $this->getEntity(PriceList::class, ['id' => 4]));
        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($dependentPriceList3 = $this->getEntity(PriceList::class, ['id' => 5]));
        $lexeme4 = new PriceRuleLexeme();
        $lexeme4->setPriceList($dependentPriceList3);

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(6))
            ->method('findEntityLexemes')
            ->willReturnMap([
                [PriceList::class, [], 1, [$lexeme1, $lexeme2]],
                [PriceList::class, [], 2, [$lexeme3, $lexeme4]],
                [PriceList::class, [], 3, []],
                [PriceList::class, [], 4, []],
                [PriceList::class, [], 5, []],
            ]);

        $this->assertEquals(
            [
                1 => $priceList1,
                2 => $priceList2,
                3 => $dependentPriceList1,
                4 => $dependentPriceList2,
                5 => $dependentPriceList3
            ],
            $this->dependentPriceListProvider->appendDependent([$priceList1, $priceList2])
        );
    }

    public function testGetResolvedOrderedDependenciesSimpleChain()
    {
        // PL1 -> PL2 -> PL3
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList2);
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList3);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(3))
            ->method('getReference')
            ->willReturnMap([
                [PriceList::class, 1, $priceList1],
                [PriceList::class, 2, $priceList2],
                [PriceList::class, 3, $priceList3],
            ]);

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(3))
            ->method('findEntityLexemes')
            ->willReturnMap([
                [PriceList::class, [], 1, [$lexeme1]],
                [PriceList::class, [], 2, [$lexeme2]],
                [PriceList::class, [], 3, []],
            ]);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('pl_deps_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->dependentPriceListProvider->getResolvedOrderedDependencies(1);

        $this->assertEquals([[1], [2], [3]], $result);
    }

    public function testGetResolvedOrderedDependenciesMultipleBranches()
    {
        // PL1 -> PL2, PL3
        // PL2 -> PL4
        // PL3 -> PL4
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);
        $priceList4 = $this->getEntity(PriceList::class, ['id' => 4]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList2);
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList3);
        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($priceList4);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(4))
            ->method('getReference')
            ->willReturnMap([
                [PriceList::class, 1, $priceList1],
                [PriceList::class, 2, $priceList2],
                [PriceList::class, 3, $priceList3],
                [PriceList::class, 4, $priceList4],
            ]);

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(4))
            ->method('findEntityLexemes')
            ->willReturnMap([
                [PriceList::class, [], 1, [$lexeme1, $lexeme2]],
                [PriceList::class, [], 2, [$lexeme3]],
                [PriceList::class, [], 3, [$lexeme3]],
                [PriceList::class, [], 4, []],
            ]);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('pl_deps_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->dependentPriceListProvider->getResolvedOrderedDependencies(1);

        $this->assertEquals([[1], [2, 3], [4]], $result);
    }

    public function testGetResolvedOrderedDependenciesNoDependencies()
    {
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('getReference')
            ->with(PriceList::class, 1)
            ->willReturn($priceList1);

        $this->priceRuleLexemeTriggerHandler->expects($this->once())
            ->method('findEntityLexemes')
            ->with(PriceList::class, [], 1)
            ->willReturn([]);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('pl_deps_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->dependentPriceListProvider->getResolvedOrderedDependencies(1);

        $this->assertEquals([[1]], $result);
    }

    public function testGetResolvedOrderedDependenciesCircularDependency()
    {
        // PL1 -> PL2 -> PL3 -> PL1 (circular)
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList2);
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList3);
        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($priceList1);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(3))
            ->method('getReference')
            ->willReturnMap([
                [PriceList::class, 1, $priceList1],
                [PriceList::class, 2, $priceList2],
                [PriceList::class, 3, $priceList3],
            ]);

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(3))
            ->method('findEntityLexemes')
            ->willReturnMap([
                [PriceList::class, [], 1, [$lexeme1]],
                [PriceList::class, [], 2, [$lexeme2]],
                [PriceList::class, [], 3, [$lexeme3]],
            ]);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('pl_deps_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected.');

        $this->dependentPriceListProvider->getResolvedOrderedDependencies(1);
    }

    public function testGetResolvedOrderedDependenciesComplexGraph()
    {
        // Complex dependency graph:
        // PL1 -> PL2, PL3
        // PL2 -> PL4, PL5
        // PL3 -> PL5
        // PL5 -> PL6
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);
        $priceList4 = $this->getEntity(PriceList::class, ['id' => 4]);
        $priceList5 = $this->getEntity(PriceList::class, ['id' => 5]);
        $priceList6 = $this->getEntity(PriceList::class, ['id' => 6]);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList2);
        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList3);
        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($priceList4);
        $lexeme4 = new PriceRuleLexeme();
        $lexeme4->setPriceList($priceList5);
        $lexeme5 = new PriceRuleLexeme();
        $lexeme5->setPriceList($priceList6);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $em->expects($this->exactly(6))
            ->method('getReference')
            ->willReturnMap([
                [PriceList::class, 1, $priceList1],
                [PriceList::class, 2, $priceList2],
                [PriceList::class, 3, $priceList3],
                [PriceList::class, 4, $priceList4],
                [PriceList::class, 5, $priceList5],
                [PriceList::class, 6, $priceList6],
            ]);

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly(6))
            ->method('findEntityLexemes')
            ->willReturnMap([
                [PriceList::class, [], 1, [$lexeme1, $lexeme2]],
                [PriceList::class, [], 2, [$lexeme3, $lexeme4]],
                [PriceList::class, [], 3, [$lexeme4]],
                [PriceList::class, [], 4, []],
                [PriceList::class, [], 5, [$lexeme5]],
                [PriceList::class, [], 6, []],
            ]);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('pl_deps_1')
            ->willReturnCallback(function ($key, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $result = $this->dependentPriceListProvider->getResolvedOrderedDependencies(1);

        $this->assertEquals([[1], [2, 3], [4, 5], [6]], $result);
    }
}
