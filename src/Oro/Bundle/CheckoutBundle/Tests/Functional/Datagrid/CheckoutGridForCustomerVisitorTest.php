<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Datagrid;

use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListCheckoutsForVisitorData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CheckoutGridForCustomerVisitorTest extends FrontendWebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadShoppingListCheckoutsForVisitorData::class]);
    }

    /**
     * @return FeatureChecker
     */
    private function getFeatureChecker()
    {
        return self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
    }

    /**
     * @return ConfigManager
     */
    private function getConfigManager()
    {
        return self::getContainer()->get('oro_config.manager');
    }

    public function testShouldDenyAccessIfGuestCheckoutIsDisabled()
    {
        // guard
        self::assertFalse($this->getFeatureChecker()->isFeatureEnabled('guest_checkout'));

        /** @var CustomerVisitor $visitor */
        $visitor = $this->getReference(LoadShoppingListCheckoutsForVisitorData::CUSTOMER_VISITOR_2);

        $this->client->getCookieJar()->set(
            new Cookie(
                AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
                base64_encode(json_encode([$visitor->getId(), $visitor->getSessionId()])),
                time() + 60
            )
        );

        $gridResponse = $this->client->requestFrontendGrid(['gridName' => 'frontend-checkouts-grid'], [], true);
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $gridResponse->getStatusCode());
    }

    public function testShouldReturnsOnlyCheckoutsBelongsToCurrentCustomerVisitor()
    {
        $configManager = $this->getConfigManager();
        $configManager->set('oro_checkout.guest_checkout', true);
        $configManager->flush();

        // guard
        self::assertTrue($this->getFeatureChecker()->isFeatureEnabled('guest_checkout'));

        /** @var CustomerVisitor $visitor */
        $visitor = $this->getReference(LoadShoppingListCheckoutsForVisitorData::CUSTOMER_VISITOR_2);
        $checkoutId = $this->getReference(LoadShoppingListCheckoutsForVisitorData::CHECKOUT_2)->getId();

        $this->client->getCookieJar()->set(
            new Cookie(
                AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
                base64_encode(json_encode([$visitor->getId(), $visitor->getSessionId()])),
                time() + 60
            )
        );

        $gridResponse = $this->client->requestFrontendGrid(['gridName' => 'frontend-checkouts-grid'], [], true);

        $responseContent = self::jsonToArray($gridResponse->getContent());
        self::assertCount(1, $responseContent['data']);
        self::assertEquals($checkoutId, $responseContent['data'][0]['id']);
    }
}
