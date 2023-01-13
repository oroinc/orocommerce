<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Voter\ShoppingListCreateVoter;

class ShoppingListCreateVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLimitManager;

    /** @var ShoppingListCreateVoter */
    private $shoppingListCreateVoter;

    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);

        $this->shoppingListCreateVoter = new ShoppingListCreateVoter($this->shoppingListLimitManager);
    }

    public function testVoteWrongFeaturePassed()
    {
        $this->assertEquals(
            VoterInterface::FEATURE_ABSTAIN,
            $this->shoppingListCreateVoter->vote('some_dummy_feature')
        );
    }

    public function testVoteFeatureEnabled()
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('isCreateEnabled')
            ->willReturn(true);

        $this->assertEquals(
            VoterInterface::FEATURE_ENABLED,
            $this->shoppingListCreateVoter->vote('shopping_list_create')
        );
    }

    public function testVoteFeatureDisabled()
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('isCreateEnabled')
            ->willReturn(false);

        $this->assertEquals(
            VoterInterface::FEATURE_DISABLED,
            $this->shoppingListCreateVoter->vote('shopping_list_create')
        );
    }
}
