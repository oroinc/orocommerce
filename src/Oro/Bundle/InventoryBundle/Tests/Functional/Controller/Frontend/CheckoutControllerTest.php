<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend\CheckoutControllerTestCase;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @dbIsolationPerTest
 * @group CommunityEdition
 */
class CheckoutControllerTest extends CheckoutControllerTestCase
{
    use ConfigManagerAwareTestTrait;

    private const CHECKOUT_STEP_LABEL = "//p[contains(@class, 'checkout-navigation__title--current')]";

    private ?int $initialMinLimit;
    private ?int $initialMaxLimit;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $configManager = self::getConfigManager();
        $this->initialMinLimit = $configManager->get('oro_inventory.minimum_quantity_to_order');
        $this->initialMaxLimit = $configManager->get('oro_inventory.maximum_quantity_to_order');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.minimum_quantity_to_order', $this->initialMinLimit);
        $configManager->set('oro_inventory.maximum_quantity_to_order', $this->initialMaxLimit);
        $configManager->flush();

        parent::tearDown();
    }

    #[\Override]
    protected function getInventoryFixtures(): array
    {
        return [LoadQuoteProductDemandData::class];
    }

    public function testRequestForQuoteCheckoutIsNotAffectedByQuantityLimits(): void
    {
        /** @var QuoteDemand $quoteDemand */
        $quoteDemand = $this->getReference(LoadQuoteProductDemandData::QUOTE_DEMAND_3);
        $lineItem = $quoteDemand->getLineItems()->first();
        $lineItem->setQuantity(3);
        self::getContainer()->get('doctrine')->getManagerForClass(LineItem::class)->flush();
        $this->startCheckoutFromQuoteDemand($quoteDemand);
        $crawler = $this->client->request('GET', self::$checkoutUrl);

        $configManager = self::getConfigManager();
        $configManager->set('oro_inventory.minimum_quantity_to_order', 1);
        $configManager->set('oro_inventory.maximum_quantity_to_order', 2);
        $configManager->flush();

        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
        $crawler = $this->goToNextStep($crawler);
        $this->assertCurrentStep($crawler);
    }

    private function assertCurrentStep(Crawler $crawler, bool $shouldBeFirstStep = false): void
    {
        $stepLabel = $crawler->filterXPath(self::CHECKOUT_STEP_LABEL)->text();
        if ($shouldBeFirstStep) {
            self::assertStringContainsString('Billing Information', $stepLabel);
        } else {
            self::assertStringNotContainsString('Billing Information', $stepLabel);
        }
    }

    private function goToNextStep(Crawler $crawler): Crawler
    {
        return $this->client->submit($this->getTransitionForm($crawler));
    }

    private function startCheckoutFromQuoteDemand(QuoteDemand $quoteDemand): void
    {
        $this->startCheckoutByData($this->getCheckoutFromQuoteDemandData($quoteDemand));
    }

    private function getCheckoutFromQuoteDemandData(QuoteDemand $quoteDemand): array
    {
        return [
            'context' => new ActionData([]),
            'options' => [
                'parameters_mapping' => [
                    'sourceCriteria' => [
                        'quoteDemand' => $quoteDemand,
                    ],
                ],
                'action_group' => 'start_checkout',
                'results' => [
                    'redirectUrl' => new PropertyPath('redirectUrl'),
                ]
            ]
        ];
    }
}
