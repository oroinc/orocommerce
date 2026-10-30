<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListCheckoutsForVisitorData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Symfony\Component\BrowserKit\Cookie;

/**
 * @dbIsolationPerTest
 */
class CheckoutNewAddressOperationVisitorAclTest extends CheckoutNewAddressOperationAclTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures([LoadShoppingListCheckoutsForVisitorData::class]);

        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', false);
        $configManager->flush();

        parent::tearDown();
    }

    /**
     * @dataProvider multiStepOperationDataProvider
     */
    public function testVisitorCanOpenAndSubmitFormOfOwnCheckout(string $operationName): void
    {
        $this->signIn(LoadShoppingListCheckoutsForVisitorData::CUSTOMER_VISITOR_1);

        $this->assertFormCanBeOpenedAndSubmitted($operationName, LoadShoppingListCheckoutsForVisitorData::CHECKOUT_1);
    }

    /**
     * @dataProvider multiStepOperationDataProvider
     */
    public function testVisitorCannotOpenOrSubmitFormOfAnotherVisitorCheckout(string $operationName): void
    {
        $this->signIn(LoadShoppingListCheckoutsForVisitorData::CUSTOMER_VISITOR_2);

        $this->assertFormCannotBeOpenedOrSubmitted(
            $operationName,
            LoadShoppingListCheckoutsForVisitorData::CHECKOUT_1,
            LoadShoppingListCheckoutsForVisitorData::CHECKOUT_2
        );
    }

    /**
     * @dataProvider operationDataProvider
     */
    public function testVisitorCannotOpenOrSubmitFormOfCustomerUserCheckout(string $operationName): void
    {
        $this->signIn(LoadShoppingListCheckoutsForVisitorData::CUSTOMER_VISITOR_1);

        $this->assertFormCannotBeOpenedOrSubmitted(
            $operationName,
            LoadShoppingListCheckoutsForVisitorData::CHECKOUT_3,
            LoadShoppingListCheckoutsForVisitorData::CHECKOUT_1
        );
    }

    public function multiStepOperationDataProvider(): array
    {
        return [
            'billing' => [self::MULTI_STEP_BILLING],
            'shipping' => [self::MULTI_STEP_SHIPPING],
        ];
    }

    public function operationDataProvider(): array
    {
        return [
            'multi-step billing' => [self::MULTI_STEP_BILLING],
            'multi-step shipping' => [self::MULTI_STEP_SHIPPING],
            'single-page billing' => [self::SINGLE_PAGE_BILLING],
            'single-page shipping' => [self::SINGLE_PAGE_SHIPPING],
        ];
    }

    private function signIn(string $visitorReference): void
    {
        /** @var CustomerVisitor $visitor */
        $visitor = $this->getReference($visitorReference);
        $this->client->getCookieJar()->set(
            new Cookie(
                AnonymousCustomerUserAuthenticator::COOKIE_NAME,
                \base64_encode(\json_encode($visitor->getSessionId(), JSON_THROW_ON_ERROR)),
                \time() + 60
            )
        );
    }
}
