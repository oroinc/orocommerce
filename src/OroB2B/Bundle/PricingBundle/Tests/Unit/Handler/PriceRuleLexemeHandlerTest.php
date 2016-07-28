<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class PriceRuleLexemeHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var PriceRuleLexemeHandler
     */
    protected $priceRuleLexemeHandler;

    /**
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceRuleProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceRuleLexemeHandler = new PriceRuleLexemeHandler(
            $this->doctrineHelper,
            $this->parser,
            $this->priceRuleProvider
        );
    }

    public function testUpdateLexemes()
    {
        $assignmentRule = 'category.id == 2 or category == 10';
        
        $rule = 'product.msrp.value + 10';
        $ruleCondition = 'product.sku == test';

        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 1]);

        $priceRule->setRule($rule);
        $priceRule->setRuleCondition($ruleCondition);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $priceList->setProductAssignmentRule($assignmentRule);
        $priceList->addPriceRule($priceRule);

        $this->parser->expects($this->any())
            ->method('getUsedLexemes')
            ->willReturnMap([
                [$assignmentRule, ['OroB2B\Bundle\ProductBundle\Entity\Category' => ['id', null]]],
                [$rule, ['OroB2B\Bundle\ProductBundle\Entity\Product::msrp' => ['value']]],
                [$ruleCondition, ['OroB2B\Bundle\ProductBundle\Entity\Product' => ['sku']]]
            ]);

        $this->priceRuleProvider->expects($this->any())
            ->method('getRealClassName')
            ->willReturnMap([
                ['OroB2B\Bundle\ProductBundle\Entity\Category', 'OroB2B\Bundle\ProductBundle\Entity\Category'],
                [
                    'OroB2B\Bundle\ProductBundle\Entity\Product::msrp',
                    'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice'
                ],
                ['OroB2B\Bundle\ProductBundle\Entity\Product', 'OroB2B\Bundle\ProductBundle\Entity\Product']
            ]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceRuleLexemeRepository $lexemeRepository */
        $lexemeRepository = $this->getMockBuilder(PriceRuleLexemeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lexemeRepository->expects($this->once())
            ->method('deleteByPriceList')
            ->with($priceList);

        $lexemeEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $lexemeEntityManager->expects($this->once())
            ->method('getRepository')
            ->with(PriceRuleLexeme::class)
            ->willReturn($lexemeRepository);

        $msrpId = '42';
        $msrpPriceAttribute = $this->getEntity(PriceAttributePriceList::class, ['id' => $msrpId]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceRuleLexemeRepository $priceAttributeRepository */
        $priceAttributeRepository = $this->getMockBuilder(PriceRuleLexemeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $priceAttributeRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['fieldName' => 'msrp'])
            ->willReturn($msrpPriceAttribute);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with(PriceRuleLexeme::class)
            ->willReturn($lexemeEntityManager);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(PriceAttributePriceList::class)
            ->willReturn($priceAttributeRepository);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');

        $lexemeEntityManager->expects($this->exactly(5))->method('persist');
        $lexemeEntityManager->expects($this->once())->method('flush');

        $this->priceRuleLexemeHandler->updateLexemes($priceList);
    }
}
