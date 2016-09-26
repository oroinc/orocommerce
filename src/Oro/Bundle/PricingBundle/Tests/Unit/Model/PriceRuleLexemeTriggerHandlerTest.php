<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PriceRuleLexemeTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListTriggerHandler;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceRuleLexemeTriggerHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->priceListTriggerHandler = $this->getMockBuilder(PriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock(RegistryInterface::class);

        $this->handler = new PriceRuleLexemeTriggerHandler($this->priceListTriggerHandler, $this->registry);
    }

    /**
     * @dataProvider criteriaDataProvider
     *
     * @param array $criteria
     * @param string $className
     * @param array $updatedFields
     * @param null|int $relationId
     */
    public function testFindEntityLexemes(array $criteria, $className, array $updatedFields = [], $relationId = null)
    {
        $lexemes = [new PriceRuleLexeme()];
        $repo = $this->getMockBuilder(PriceRuleLexemeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findBy')
            ->with($criteria)
            ->willReturn($lexemes);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceRuleLexeme::class)
            ->willReturn($em);

        $this->assertEquals($lexemes, $this->handler->findEntityLexemes($className, $updatedFields, $relationId));
    }

    /**
     * @return array
     */
    public function criteriaDataProvider()
    {
        return [
            [
                ['className' => 'TestClass'],
                'TestClass'
            ],
            [
                ['className' => 'TestClass', 'fieldName' => ['test']],
                'TestClass',
                ['test']
            ],
            [
                ['className' => 'TestClass', 'relationId' => 1],
                'TestClass',
                [],
                1
            ],
            [
                ['className' => 'TestClass', 'fieldName' => ['test'], 'relationId' => 1],
                'TestClass',
                ['test'],
                1
            ],
        ];
    }

    /**
     * @dataProvider productDataProvider
     *
     * @param Product|null $product
     */
    public function testAddTriggersByLexemes(Product $product = null)
    {
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getEntity(PriceList::class, ['id' => 1]);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);

        $priceLists = [1 => $priceList1, 2 => $priceList2];

        $repo = $this->getMockBuilder(PriceListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('updatePriceListsActuality')
            ->with($priceLists, false);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

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
            ->method('addTriggerForPriceList')
            ->withConsecutive(
                [Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, $priceList1, $product],
                [Topics::RESOLVE_PRICE_RULES, $priceList2, $product]
            );

        $this->handler->addTriggersByLexemes($lexemes, $product);
    }

    /**
     * @return array
     */
    public function productDataProvider()
    {
        return [
            [null],
            [new Product()]
        ];
    }
}
