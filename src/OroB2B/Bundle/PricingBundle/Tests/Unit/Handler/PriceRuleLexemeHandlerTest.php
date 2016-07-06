<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;

class PriceRuleLexemeHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var PriceRuleLexemeHandler
     */
    protected $priceRuleLexemeHandler;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $this->priceRuleLexemeHandler = new PriceRuleLexemeHandler($this->registry, $this->parser);
    }

    public function testUpdateLexemes()
    {
        $condition1 = 'condition1';
        $condition2 = 'condition2';
        $condition3 = 'condition3';
        $condition4 = 'condition4';
        /** @var PriceRule $priceRule1 */
        $priceRule1 = $this->getEntity(PriceRule::class, ['id' => 1]);
        /** @var PriceRule $priceRule2 */
        $priceRule2 = $this->getEntity(PriceRule::class, ['id' => 2]);
        $priceRule1->setRule($condition2);
        $priceRule1->setRuleCondition($condition4);
        $priceRule2->setRule($condition3);
        $priceRule2->setRuleCondition($condition4);
        $priceList = new PriceList();
        $priceList->setProductAssignmentRule($condition1);
        $priceList->addPriceRule($priceRule1);
        $priceList->addPriceRule($priceRule2);

        $map = [
            [$condition1, ['class1' => ['field1', null], 'class2' => [null]]],
            [$condition2, ['class1' => ['field3', 'field2'], 'class3' => ['field3', 'field6']]],
            [$condition3, ['class2' => ['field5', null], 'class3' => ['field3', 'field1']]],
            [$condition4, ['class1' => [null, 'field1'], 'class2' => ['field3', 'field1']]],
        ];
        $this->parser->expects($this->any())
            ->method('getUsedLexemes')
            ->will($this->returnValueMap($map));
        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceRuleLexemeRepository $repo */
        $repo = $this->getMockBuilder(PriceRuleLexemeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getLexemesByRules')
            ->with(new ArrayCollection([$priceRule1, $priceRule2]))
            ->willReturn($this->getExistLexemes());
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BPricingBundle:PriceRuleLexeme')
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BPricingBundle:PriceRuleLexeme')
            ->willReturn($em);
        $em->expects($this->exactly(1))->method('remove');
        $em->expects($this->exactly(18))->method('persist');
        $em->expects($this->once())->method('flush');
        $this->priceRuleLexemeHandler->updateLexemes($priceList);
    }

    /**
     * @return PriceRuleLexeme[]
     */
    protected function getExistLexemes()
    {
        $rule1 = $this->getEntity(PriceRule::class, ['id' => 1]);
        $rule2 = $this->getEntity(PriceRule::class, ['id' => 2]);
        $rule3 = $this->getEntity(PriceRule::class, ['id' => 3]);
        $lexeme1 = $this->getEntity(
            PriceRuleLexeme::class,
            ['id' => 1, 'className' => 'class1', 'fieldName' => null, 'priceRule' => $rule1]
        );
        $lexeme2 = $this->getEntity(
            PriceRuleLexeme::class,
            [
                'id' => 2,
                'className' => 'class2',
                'fieldName' => 'field4',
                'priceRule' => null,
                'priceList' => new PriceList(),
            ]
        );
        $lexeme3 = $this->getEntity(
            PriceRuleLexeme::class,
            ['id' => 3, 'className' => 'class3', 'fieldName' => 'field4', 'priceRule' => $rule3]
        );

        return [$lexeme1, $lexeme2, $lexeme3];
    }
}
