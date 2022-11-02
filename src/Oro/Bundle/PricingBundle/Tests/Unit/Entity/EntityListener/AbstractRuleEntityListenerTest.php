<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\EntityListener\AbstractRuleEntityListener;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractRuleEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PriceRuleLexemeTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $priceRuleLexemeTriggerHandler;

    /** @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldsProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var AbstractRuleEntityListener */
    protected $listener;

    protected function setUp(): void
    {
        $this->priceRuleLexemeTriggerHandler = $this->createMock(PriceRuleLexemeTriggerHandler::class);
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener = $this->getListener();
    }

    protected function tearDown(): void
    {
        unset(
            $this->priceListTriggerHandler,
            $this->fieldsProvider,
            $this->registry,
            $this->listener,
            $this->featureChecker
        );
    }

    /**
     * @return string
     */
    abstract protected function getEntityClassName();

    /**
     * @return AbstractRuleEntityListener
     */
    abstract protected function getListener();

    /**
     * @param string $feature
     * @param bool $isEnabled
     * @return AbstractRuleEntityListener
     */
    protected function assertFeatureChecker(string $feature, bool $isEnabled = true)
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with($feature, null)
            ->willReturn($isEnabled);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature($feature);

        return $this->listener;
    }

    protected function assertRecalculateByEntityFieldsUpdate(
        int $providerNumberOfCalls,
        int $handlerNumberOfCalls,
        array $expectedFields,
        array $changeSet,
        Product $product = null,
        int $relationId = null
    ) {
        $updatedFields = array_intersect($expectedFields, array_keys($changeSet));

        $this->fieldsProvider->expects($this->exactly($providerNumberOfCalls))
            ->method('getFields')
            ->with($this->getEntityClassName(), false, true)
            ->willReturn($expectedFields);

        $this->assertRecalculateByEntity($handlerNumberOfCalls, $updatedFields, [$product], $relationId);
    }

    /**
     * @param int $numberOfCalls
     * @param array $updatedFields
     * @param array $products
     * @param null $relationId
     */
    protected function assertRecalculateByEntity(
        int $numberOfCalls,
        array $updatedFields,
        array $products = [],
        $relationId = null
    ) {
        $this->priceRuleLexemeTriggerHandler->expects($this->exactly($numberOfCalls))
            ->method('findEntityLexemes')
            ->with($this->getEntityClassName(), $updatedFields, $relationId)
            ->willReturn([]);

        $this->priceRuleLexemeTriggerHandler->expects($this->exactly($numberOfCalls))
            ->method('processLexemes')
            ->with([], $products);
    }
}
