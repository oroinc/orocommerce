<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListReferenceCheckerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var PriceListReferenceChecker */
    private $priceListReferenceChecker;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->priceListReferenceChecker = new PriceListReferenceChecker($this->registry);
    }

    public function testReferential()
    {
        $em = $this->createMock(ObjectManager::class);
        $repository = $this->createMock(PriceRuleLexemeRepository::class);

        $repository->expects($this->once())
            ->method('getRelationIds')
            ->willReturn([1, 2]);

        $em->expects($this->any())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceRuleLexeme::class)
            ->willReturn($em);

        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $this->assertTrue($this->priceListReferenceChecker->isReferential($priceList));
    }

    public function testNotReferential()
    {
        $em = $this->createMock(ObjectManager::class);
        $repository = $this->createMock(PriceRuleLexemeRepository::class);

        $repository->expects($this->once())
            ->method('getRelationIds')
            ->willReturn([10, 20]);

        $em->expects($this->any())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceRuleLexeme::class)
            ->willReturn($em);

        $priceList = $this->getEntity(PriceList::class, ['id' => 8]);
        $this->assertFalse($this->priceListReferenceChecker->isReferential($priceList));
    }
}
