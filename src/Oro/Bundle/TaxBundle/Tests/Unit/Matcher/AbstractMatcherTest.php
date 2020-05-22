<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Matcher;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\AbstractMatcher;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractMatcherTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const TAX_RULE_CLASS = 'Oro\Bundle\TaxBundle\Entity\TaxRule';

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var AbstractMatcher
     */
    protected $matcher;

    /**
     * @var TaxRuleRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxRuleRepository;

    protected function setUp(): void
    {
        $this->taxRuleRepository = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\TaxRuleRepository')
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

    protected function tearDown(): void
    {
        unset($this->matcher, $this->doctrineHelper, $this->taxRuleRepository);
    }

    /**
     * @param int $id
     * @return TaxRule
     */
    protected function getTaxRule($id)
    {
        return $this->getEntity('Oro\Bundle\TaxBundle\Entity\TaxRule', ['id' => $id]);
    }
}
