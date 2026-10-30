<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutUserACLData;

/**
 * @dbIsolationPerTest
 */
class CheckoutNewAddressOperationAclTest extends CheckoutNewAddressOperationAclTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures([LoadCheckoutACLData::class]);
    }

    /**
     * @dataProvider operationDataProvider
     */
    public function testOwnerCanOpenAndSubmitForm(string $operationName, string $checkoutReference): void
    {
        $this->signIn(LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_LOCAL);

        $this->assertFormCanBeOpenedAndSubmitted($operationName, $checkoutReference);
    }

    /**
     * @dataProvider operationDataProvider
     */
    public function testUserOfAnotherCustomerCannotOpenOrSubmitForm(
        string $operationName,
        string $checkoutReference
    ): void {
        $this->signIn(LoadCheckoutUserACLData::USER_ACCOUNT_2_ROLE_LOCAL);

        $this->assertFormCannotBeOpenedOrSubmitted(
            $operationName,
            $checkoutReference,
            LoadCheckoutACLData::CHECKOUT_ACC_2_USER_LOCAL
        );
    }

    public function operationDataProvider(): array
    {
        return [
            'multi-step billing' => [
                self::MULTI_STEP_BILLING,
                LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL
            ],
            'multi-step shipping' => [
                self::MULTI_STEP_SHIPPING,
                LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL
            ],
            'single-page billing' => [
                self::SINGLE_PAGE_BILLING,
                LoadCheckoutACLData::SINGLE_STEP_CHECKOUT_ACC_1_USER_LOCAL,
            ],
            'single-page shipping' => [
                self::SINGLE_PAGE_SHIPPING,
                LoadCheckoutACLData::SINGLE_STEP_CHECKOUT_ACC_1_USER_LOCAL,
            ],
        ];
    }

    private function signIn(string $user): void
    {
        $this->initClient([], self::generateBasicAuthHeader($user, $user));
        $this->setCurrentWebsite('default');
    }
}
