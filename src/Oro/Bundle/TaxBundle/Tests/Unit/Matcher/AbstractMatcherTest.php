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

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var AbstractMatcher */
    protected $matcher;

    /** @var TaxRuleRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $taxRuleRepository;

    protected function setUp(): void
    {
        $this->taxRuleRepository = $this->createMock(TaxRuleRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(TaxRule::class)
            ->willReturn($this->taxRuleRepository);
    }

    protected function getTaxRule(int $id): TaxRule
    {
        return $this->getEntity(TaxRule::class, ['id' => $id]);
    }
}
