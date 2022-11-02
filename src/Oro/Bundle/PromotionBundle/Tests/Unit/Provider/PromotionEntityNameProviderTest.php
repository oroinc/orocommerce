<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\PromotionEntityNameProvider;
use Oro\Bundle\RuleBundle\Entity\Rule;

class PromotionEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private PromotionEntityNameProvider $provider;

    private Promotion $promotion;

    protected function setUp(): void
    {
        $this->provider = new PromotionEntityNameProvider();

        $rule = new Rule();
        $rule->setName('test name');

        $this->promotion = new Promotion();
        $this->promotion->setRule($rule);
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        self::assertEquals(
            $this->promotion->getRule()->getName(),
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', $this->promotion)
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', Promotion::class, 'promotion')
        );
    }
}
