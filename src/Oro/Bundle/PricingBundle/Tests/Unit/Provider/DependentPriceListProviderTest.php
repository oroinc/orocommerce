<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\DependentPriceListProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class DependentPriceListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PriceRuleLexemeTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceRuleLexemeTriggerHandler;

    /** @var DependentPriceListProvider */
    private $dependentPriceListProvider;

    protected function setUp(): void
    {
        $this->priceRuleLexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);

        $this->dependentPriceListProvider = new DependentPriceListProvider($this->priceRuleLexemeTriggerHandler);
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
}
