<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsActivationRules;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductAdditionalPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Component\MessageQueue\Client\Message;

class CombinedPriceListScheduleResolverTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $resolver;

    /**
     * @var CombinedProductPriceRepository
     */
    protected $priceRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadCombinedPriceLists::class,
                LoadCombinedPriceListsActivationRules::class,
                LoadCombinedProductPrices::class,
                LoadCombinedProductAdditionalPrices::class
            ]
        );
        $this->resolver = $this->getContainer()->get('oro_pricing.resolver.combined_product_schedule_resolver');
        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->priceRepository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedProductPrice::class)
            ->getRepository(CombinedProductPrice::class);
    }

    /**
     * @dataProvider cplSwitchingDataProvider
     * @param array $cplRelationsExpected
     * @param array $cplConfig
     * @param \DateTime $now
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

        $collector = self::getMessageCollector();
        $collector->clear();
        $this->resolver->updateRelations($now);
        //if price list is empty there is no need to send messages
        $messages = $collector->getTopicSentMessages(AsyncIndexer::TOPIC_REINDEX);
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

        $sentMessages = $this->getSentMessages();
        $this->assertCount(1, $sentMessages);
        $messageData = reset($sentMessages);
        $this->assertEquals('oro.website.search.indexer.reindex', $messageData['topic']);
        /** @var Message $message */
        $message = $messageData['message'];
        $messageBody = $message->getBody();
        $this->assertEquals(Product::class, $messageBody['class'][0]);
        $actualProducts = $messageBody['context']['entityIds'];
        sort($actualProducts);
        $this->assertEquals($expectedProducts, $actualProducts, 'Re-indexed products does not match expected');
    }

    /**
     * @return array
     */
    public function cplSwitchingDataProvider()
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

    /**
     * @param string $modifyStr
     * @return \DateTime
     */
    protected function createDateTime($modifyStr)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->modify($modifyStr);

        return $date;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->getContainer()->get('doctrine')
                ->getManagerForClass(CombinedPriceListActivationRule::class);
        }

        return $this->manager;
    }

    /**
     * @param $entityName
     * @param CombinedPriceList $fullCPL
     * @param CombinedPriceList $currentCPL
     * @return BaseCombinedPriceListRelation[]
     */
    protected function getInvalidRelations($entityName, CombinedPriceList $fullCPL, CombinedPriceList $currentCPL)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('r')
            ->from($entityName, 'r')
            ->where('r.fullChainPriceList = :fullCPl AND r.priceList != :currentCPL')
            ->setParameter('fullCPl', $fullCPL)
            ->setParameter('currentCPL', $currentCPL);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $cplConfig
     */
    protected function setConfigCPL(array $cplConfig)
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

    /**
     * @param array $cplConfig
     */
    protected function checkConfigCPL(array $cplConfig)
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
