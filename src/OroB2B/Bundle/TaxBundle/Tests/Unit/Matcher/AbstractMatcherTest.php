<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\AbstractMatcher;

abstract class AbstractMatcherTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const TAX_RULE_CLASS = 'OroB2B\Bundle\TaxBundle\Entity\TaxRule';

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AbstractMatcher
     */
    protected $matcher;

    /**
     * @var TaxRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxRuleRepository;

    protected function setUp()
    {
        $this->taxRuleRepository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(self::TAX_RULE_CLASS)
            ->willReturn($this->taxRuleRepository);
    }

    protected function tearDown()
    {
        unset($this->matcher, $this->doctrineHelper, $this->taxRuleRepository);
    }

    /**
     * @param int $id
     * @return TaxRule
     */
    protected function getTaxRule($id)
    {
        return $this->getEntity('OroB2B\Bundle\TaxBundle\Entity\TaxRule', ['id' => $id]);
    }
}
