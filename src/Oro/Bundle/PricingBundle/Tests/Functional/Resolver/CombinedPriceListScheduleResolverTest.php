<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsActivationRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductAdditionalPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;

class CombinedPriceListScheduleResolverTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    private CombinedPriceListScheduleResolver $resolver;
    private ConfigManager $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadCombinedPriceLists::class,
            LoadCombinedPriceListsActivationRules::class,
            LoadCombinedProductPrices::class,
            LoadCombinedProductAdditionalPrices::class
        ]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');

        $this->resolver = $this->getContainer()->get('oro_pricing.resolver.combined_product_schedule_resolver');
        $this->configManager = self::getConfigManager();
    }

    /**
     * @dataProvider cplSwitchingDataProvider
     */
    public function testCPLSwitching(array $cplRelationsExpected, array $cplConfig, \DateTime $now)
    {
        $this->setConfigCPL($cplConfig);
        $fullCPLName = $cplRelationsExpected['full'];
        $currentCPLName = $cplRelationsExpected['actual'];
        /** @var CombinedPriceList $fullCPL */
        $fullCPL = $this->getReference($fullCPLName);
        /** @var CombinedPriceList $currentCPL */
        $currentCPL = $this->getReference($currentCPLName);

        self::clearMessageCollector();
        $this->resolver->updateRelations($now);
        //if price list is empty there is no need to send messages
        $messages = self::getTopicSentMessages(WebsiteSearchReindexTopic::getName());
        $this->assertNotEmpty($messages);

        $relations = $this->getInvalidRelations(
            CombinedPriceListToCustomer::class,
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $relations = $this->getInvalidRelations(
            CombinedPriceListToCustomerGroup::class,
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $relations = $this->getInvalidRelations(
            CombinedPriceListToWebsite::class,
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $this->checkConfigCPL($cplConfig);

        $expectedProducts = [];
        foreach ($cplRelationsExpected['products'] as $product) {
            $expectedProducts[] = $this->getReference($product)->getId();
        }
        sort($expectedProducts);

        $sentMessages = self::getSentMessages();
        $this->assertCount(1, $sentMessages);
        $messageData = reset($sentMessages);
        $this->assertEquals('oro.website.search.indexer.reindex', $messageData['topic']);
        $messageBody = $messageData['message'];
        $this->assertEquals(Product::class, $messageBody['class'][0]);
        $actualProducts = $messageBody['context']['entityIds'];
        sort($actualProducts);
        $this->assertEquals($expectedProducts, $actualProducts, 'Re-indexed products does not match expected');
    }

    public function cplSwitchingDataProvider(): array
    {
        return [
            [
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f_1t_3t',
                    'products' => [
                        LoadProductData::PRODUCT_1,
                        LoadProductData::PRODUCT_2
                    ]
                ],
                'cplConfig' => [
                    'actualCpl' => '2f_1t_3t',
                    'fullCpl' => '2f_1t_3t',
                    'expectedActualCpl' => '2f_1t_3t',
                    'expectedFullCpl' => '2f_1t_3t',
                ],
                'now' => $this->createDateTime('+11 hours'),
            ],
            [
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f',
                    'products' => [
                        LoadProductData::PRODUCT_1,
                        LoadProductData::PRODUCT_2
                    ]
                ],
                'cplConfig' => [
                    'actualCpl' => '2f_1t_3t',
                    'fullCpl' => '2f_1t_3t',
                    'expectedActualCpl' => '2f',
                    'expectedFullCpl' => '2f_1t_3t',
                ],
                'now' => $this->createDateTime('+50 hours'),
            ],
            [
                'cplRelationsExpected' => [
                    'full' => '1f',
                    'actual' => '2f',
                    'products' => [
                        LoadProductData::PRODUCT_1,
                        LoadProductData::PRODUCT_2
                    ]
                ],
                'cplConfig' => [
                    'actualCpl' => '1f',
                    'fullCpl' => '1f',
                    'expectedActualCpl' => '2f',
                    'expectedFullCpl' => '1f',
                ],
                'now' => $this->createDateTime('+4 days'),
            ],
        ];
    }

    private function createDateTime(string $modifyStr): \DateTime
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify($modifyStr);

        return $date;
    }

    /**
     * @param string            $entityName
     * @param CombinedPriceList $fullCPL
     * @param CombinedPriceList $currentCPL
     *
     * @return BaseCombinedPriceListRelation[]
     */
    private function getInvalidRelations(
        string $entityName,
        CombinedPriceList $fullCPL,
        CombinedPriceList $currentCPL
    ): array {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(CombinedPriceListActivationRule::class);
        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from($entityName, 'r')
            ->where('r.fullChainPriceList = :fullCPl AND r.priceList != :currentCPL')
            ->setParameter('fullCPl', $fullCPL)
            ->setParameter('currentCPL', $currentCPL);

        return $qb->getQuery()->getResult();
    }

    private function setConfigCPL(array $cplConfig)
    {
        $actualCPLConfigKey = Configuration::getConfigKeyToPriceList();
        $fullCPLConfigKey = Configuration::getConfigKeyToFullPriceList();
        $fullCpl = null;
        $actualCpl = null;
        if ($cplConfig['actualCpl']) {
            $actualCpl = $this->getReference($cplConfig['actualCpl'])->getId();
        }
        if ($cplConfig['fullCpl']) {
            $fullCpl = $this->getReference($cplConfig['fullCpl'])->getId();
        }
        $this->configManager->set($actualCPLConfigKey, $actualCpl);
        $this->configManager->set($fullCPLConfigKey, $fullCpl);

        $this->configManager->flush();
    }

    private function checkConfigCPL(array $cplConfig)
    {
        $this->configManager->reload();
        $fullCPLConfigKey = Configuration::getConfigKeyToFullPriceList();
        $actualCPLConfigKey = Configuration::getConfigKeyToPriceList();
        $expectedActualCpl = null;
        $expectedFullCpl = null;
        if ($cplConfig['expectedActualCpl']) {
            $expectedActualCpl = $this->getReference($cplConfig['expectedActualCpl'])->getId();
        }
        if ($cplConfig['expectedFullCpl']) {
            $expectedFullCpl = $this->getReference($cplConfig['expectedFullCpl'])->getId();
        }
        $this->assertEquals($expectedActualCpl, $this->configManager->get($actualCPLConfigKey));
        $this->assertEquals($expectedFullCpl, $this->configManager->get($fullCPLConfigKey));
    }
}
