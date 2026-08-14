<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
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
    public function testFindEntityLexemes(string $className, array $updatedFields = [], ?int $relationId = null): void
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

        self::assertEquals($lexemes, $this->handler->findEntityLexemes($className, $updatedFields, $relationId));
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

    public function testProcessLexemesWithoutProducts(): void
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
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, []],
                [ResolvePriceRulesTopic::getName(), $priceList2, []]
            );

        $this->handler->processLexemes($lexemes, []);
    }

    public function testProcessLexemesWithAssignedProduct(): void
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 10]);
        $product->setOrganization($organization);

        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList1->setOrganization($organization);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList2->setOrganization($organization);

        $priceLists = [1 => $priceList1, 2 => $priceList2];

        $priceListRepo = self::createMock(PriceListRepository::class);
        $priceListRepo->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with($priceLists, false);

        $priceListToProductRepo = self::createMock(PriceListToProductRepository::class);
        $priceListToProductRepo->expects(self::exactly(2))
            ->method('getAssignedProductIdsAmong')
            ->willReturn([$product->getId()]);

        $this->doctrine->expects(self::exactly(3))
            ->method('getRepository')
            ->willReturnCallback(static function (string $class) use ($priceListRepo, $priceListToProductRepo) {
                return match ($class) {
                    PriceList::class => $priceListRepo,
                    PriceListToProduct::class => $priceListToProductRepo,
                    default => null,
                };
            });

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
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, [$product]],
                [ResolvePriceRulesTopic::getName(), $priceList2, [$product]]
            );

        $this->handler->processLexemes($lexemes, [$product]);
    }

    public function testProcessLexemesSkipsNotActualUpdateForNotAssignedProductsWithPriceRule(): void
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 10]);
        $product->setOrganization($organization);

        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList1->setOrganization($organization);
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList2->setOrganization($organization);

        $priceListRepo = self::createMock(PriceListRepository::class);
        $priceListRepo->expects(self::once())
            ->method('updatePriceListsActuality')
            ->with([1 => $priceList1], false);

        $priceListToProductRepo = self::createMock(PriceListToProductRepository::class);
        $priceListToProductRepo->expects(self::once())
            ->method('getAssignedProductIdsAmong')
            ->with($priceList2, [$product->getId()])
            ->willReturn([]);

        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnCallback(static function (string $class) use ($priceListRepo, $priceListToProductRepo) {
                return match ($class) {
                    PriceList::class => $priceListRepo,
                    PriceListToProduct::class => $priceListToProductRepo,
                    default => null,
                };
            });

        $lexeme1 = new PriceRuleLexeme();
        $lexeme1->setPriceList($priceList1);

        $lexeme2 = new PriceRuleLexeme();
        $lexeme2->setPriceList($priceList2);
        $lexeme2->setPriceRule(new PriceRule());

        $lexemes = [$lexeme1, $lexeme2];

        $this->priceListTriggerHandler->expects(self::exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, [$product]],
                [ResolvePriceRulesTopic::getName(), $priceList2, [$product]]
            );

        $this->handler->processLexemes($lexemes, [$product]);
    }

    public function testProcessLexemesWithNullProductSentinel(): void
    {
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setOrganization($organization);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $lexeme = new PriceRuleLexeme();
        $lexeme->setPriceList($priceList);

        $this->priceListTriggerHandler->expects(self::once())
            ->method('handlePriceListTopic')
            ->with(ResolvePriceListAssignedProductsTopic::getName(), $priceList, [null]);

        $this->handler->processLexemes([$lexeme], [null]);
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
                [ResolvePriceListAssignedProductsTopic::getName(), $priceList1, [$product]],
                [ResolvePriceRulesTopic::getName(), $priceList2, [$product]]
            );

        $this->handler->processLexemes($lexemes, [$product]);
    }
}
