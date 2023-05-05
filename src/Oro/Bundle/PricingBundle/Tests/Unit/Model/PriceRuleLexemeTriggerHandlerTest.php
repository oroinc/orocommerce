<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceRuleLexemeTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceRuleLexemeTriggerHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->handler = new PriceRuleLexemeTriggerHandler($this->priceListTriggerHandler, $this->doctrine);
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testFindEntityLexemes(string $className, array $updatedFields = [], int $relationId = null)
    {
        $lexemes = [new PriceRuleLexeme()];
        $repo = $this->createMock(PriceRuleLexemeRepository::class);
        $repo->expects($this->once())
            ->method('findEntityLexemes')
            ->with($className, $updatedFields, $relationId)
            ->willReturn($lexemes);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($repo);

        $this->assertEquals($lexemes, $this->handler->findEntityLexemes($className, $updatedFields, $relationId));
    }

    public function criteriaDataProvider(): array
    {
        return [
            [
                'TestClass'
            ],
            [
                'TestClass',
                ['test']
            ],
            [
                'TestClass',
                [],
                1
            ],
            [
                'TestClass',
                ['test'],
                1
            ],
        ];
    }

    /**
     * @dataProvider productDataProvider
     */
    public function testProcessLexemes(Product $product = null)
    {
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        $priceLists = [1 => $priceList1, 2 => $priceList2];

        $repo = $this->createMock(PriceListRepository::class);
        $repo->expects($this->once())
            ->method('updatePriceListsActuality')
            ->with($priceLists, false);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repo);

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList1);

        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList1);
        $lexeme2->setPriceRule(new PriceRule());

        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($priceList2);
        $lexeme3->setPriceRule(new PriceRule());

        $lexemes = [$lexeme1, $lexeme2, $lexeme3];

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, $product ? [$product] : []],
                [ResolvePriceRulesTopic::getName(), $priceList2, $product ? [$product] : []]
            );

        $this->handler->processLexemes($lexemes, $product ? [$product] : []);
    }

    public function productDataProvider(): array
    {
        return [
            [null],
            [new Product()]
        ];
    }

    public function testProcessLexemesWhenNoLexemes()
    {
        $this->doctrine->expects($this->never())
            ->method('getRepository');

        $this->priceListTriggerHandler->expects($this->never())
            ->method('handlePriceListTopic');

        $this->handler->processLexemes([], [new Product()]);
    }
}
