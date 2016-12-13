<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationService;

class ExpressionLanguageRuleFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const EXPRESSION_VARIABLE = 'test';

    /** @internal */
    const EXPRESSION_VALUE = 1;

    /** @var ExpressionLanguageRuleFiltrationService */
    private $service;

    protected function setUp()
    {
        $this->service = new ExpressionLanguageRuleFiltrationService();
    }

    /**
     * @dataProvider ruleOwnersDataProvider
     *
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param RuleOwnerInterface[]|array $expectedRuleOwners
     */
    public function testGetFilteredRuleOwners($ruleOwners, $expectedRuleOwners)
    {
        $values = [self::EXPRESSION_VARIABLE => self::EXPRESSION_VALUE];

        $actualRuleOwners = $this->service->getFilteredRuleOwners($ruleOwners, $values);

        static::assertSame($expectedRuleOwners, $actualRuleOwners);
    }

    /**
     * @return array
     */
    public function ruleOwnersDataProvider()
    {
        $notApplicable = $this->createNotApplicableOwner('1', false);
        $notApplicableStop = $this->createNotApplicableOwner('2', true);

        $applicable = $this->createApplicableOwner('3', false);
        $applicableStop = $this->createApplicableOwner('4', true);

        $exceptionOwner = $this->createExceptionOwner();

        return [
            'testOnlyApplicablePassAndFinishOnApplicableStop' => [
                [$notApplicable, $notApplicableStop, $applicable, $applicableStop, $applicable],
                [$applicable, $applicableStop]
            ],
            'testWithWrongLanguageExpression' => [
                [$applicable, $exceptionOwner, $notApplicableStop, $applicableStop, $applicable],
                [$applicable, $applicableStop]
            ],
        ];
    }

    /**
     * @param string $name
     * @param bool   $stopProcessing
     *
     * @return RuleOwnerInterface
     */
    private function createNotApplicableOwner($name, $stopProcessing)
    {
        $rule = new Rule();

        $rule->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' > ' . self::EXPRESSION_VALUE)
            ->setStopProcessing($stopProcessing);

        return $this->createRuleOwner($rule);
    }

    /**
     * @param string $name
     * @param bool   $stopProcessing
     *
     * @return RuleOwnerInterface
     */
    private function createApplicableOwner($name, $stopProcessing)
    {
        $rule = new Rule();

        $rule->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' = ' . self::EXPRESSION_VALUE)
            ->setStopProcessing($stopProcessing);

        return $this->createRuleOwner($rule);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RuleOwnerInterface
     */
    private function createExceptionOwner()
    {
        $rule = new Rule();

        $rule->setExpression('t = %');

        return $this->createRuleOwner($rule);
    }

    /**
     * @param Rule $rule
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|RuleOwnerInterface
     */
    private function createRuleOwner(Rule $rule)
    {
        $ruleOwner = $this->getMock(RuleOwnerInterface::class, ['getRule']);
        $ruleOwner->expects(static::any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }
}
