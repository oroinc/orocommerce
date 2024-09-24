<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->handler = new PriceRuleLexemeTriggerHandler($this->priceListTriggerHandler, $this->doctrine);
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testFindEntityLexemes(string $className, array $updatedFields = [], int $relationId = null): void
    {
        $lexemes = [new PriceRuleLexeme()];
        $repo = $this->createMock(PriceRuleLexemeRepository::class);
        $repo->expects(self::once())
            ->method('findEntityLexemes')
            ->with($className, $updatedFields, $relationId)
            ->willReturn($lexemes);

        $this->doctrine->expects(self::once())
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
    public function testProcessLexemes(Product $product = null): void
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList1->setOrganization($organization);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList2->setOrganization($organization);

        $priceLists = [1 => $priceList1, 2 => $priceList2];

        $repo = self::createMock(PriceListRepository::class);
        $repo->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with($priceLists, false);

        $this->doctrine->expects(self::once())
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

        $this->priceListTriggerHandler->expects(self::exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, $product ? [$product] : []],
                [ResolvePriceRulesTopic::getName(), $priceList2, $product ? [$product] : []]
            );

        $this->handler->processLexemes($lexemes, $product ? [$product] : []);
    }

    public function productDataProvider(): array
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $product = new Product();
        $product->setOrganization($organization);
        return [
            [null],
            [$product]
        ];
    }

    public function testProcessLexemesWhenNoLexemes(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->priceListTriggerHandler->expects(self::never())
            ->method('handlePriceListTopic');

        $this->handler->processLexemes([], [new Product()]);
    }

    public function testProcessLexemesForPriceListFromAnotherOrganization(): void
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList1->setOrganization($organization);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList2->setOrganization($organization);

        $organization1 = $this->getEntity(Organization::class, ['id' => 2]);
        $product = new Product();
        $product->setOrganization($organization1);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList1);

        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList1);
        $lexeme2->setPriceRule(new PriceRule());

        $lexeme3 = new PriceRuleLexeme();
        $lexeme3->setPriceList($priceList2);
        $lexeme3->setPriceRule(new PriceRule());

        $lexemes = [$lexeme1, $lexeme2, $lexeme3];

        $this->priceListTriggerHandler->expects(self::exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, $product ? [$product] : []],
                [ResolvePriceRulesTopic::getName(), $priceList2, $product ? [$product] : []]
            );

        $this->handler->processLexemes($lexemes, $product ? [$product] : []);
    }
}
