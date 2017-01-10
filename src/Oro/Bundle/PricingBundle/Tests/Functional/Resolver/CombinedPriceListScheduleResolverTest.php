<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;

/**
 * @dbIsolation
 */
class CombinedPriceListScheduleResolverTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $resolver;

    /**
     * @var MinimalProductPriceRepository
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
     * @var int|null
     */
    protected $defaultPriceListId;

    /**
     * @var int|null
     */
    protected $defaultFullPriceListId;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsActivationRules',
                LoadCombinedProductPrices::class
            ]
        );
        $this->resolver = $this->getContainer()->get('oro_pricing.resolver.combined_product_schedule_resolver');
        $this->configManager = $this->getContainer()->get('oro_config.global');
        $this->priceRepository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(MinimalProductPrice::class)
            ->getRepository(MinimalProductPrice::class);

        $this->saveDefaultConfigValue();
    }

    protected function tearDown()
    {
        $this->restoreConfigValue();
        parent::tearDown();
    }

    /**
     * @dataProvider CPLSwitchingDataProvider
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
        $products = $this->priceRepository->getProductIdsByPriceLists([$currentCPL]);
        $messages = $collector->getTopicSentMessages(AsyncIndexer::TOPIC_REINDEX);
        if ($products) {
            $this->assertNotEmpty($messages);
        } else {
            $this->assertEmpty($messages);
        }

        $relations = $this->getInvalidRelations(
            'OroPricingBundle:CombinedPriceListToCustomer',
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $relations = $this->getInvalidRelations(
            'OroPricingBundle:CombinedPriceListToCustomerGroup',
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $relations = $this->getInvalidRelations(
            'OroPricingBundle:CombinedPriceListToWebsite',
            $fullCPL,
            $currentCPL
        );
        $this->assertEmpty($relations);
        $this->checkConfigCPL($cplConfig);
    }

    /**
     * @return array
     */
    public function CPLSwitchingDataProvider()
    {
        return [
            [
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f_1t_3t',
                ],
                'cplConfig' => [
                    'actualCpl' => '2f_1t_3t',
                    'fullCpl' => '2f_1t_3t',
                    'expectedActualCpl' => '2f_1t_3t',
                    'expectedFullCpl' => '2f_1t_3t',
                ],
                'now' => $this->createDateTime('+2 day'),
            ],
            [
                'cplRelationsExpected' => [
                    'full' => '2f_1t_3t',
                    'actual' => '2f',
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
                ->getManagerForClass('OroPricingBundle:CombinedPriceListActivationRule');
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
        $this->assertSame($expectedActualCpl, $this->configManager->get($actualCPLConfigKey));
        $this->assertSame($expectedFullCpl, $this->configManager->get($fullCPLConfigKey));
    }

    protected function saveDefaultConfigValue()
    {
        $this->defaultPriceListId = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $this->defaultFullPriceListId = $this->configManager->get(Configuration::getConfigKeyToFullPriceList());
    }

    protected function restoreConfigValue()
    {
        $this->configManager->set(Configuration::getConfigKeyToPriceList(), $this->defaultPriceListId);
        $this->configManager->set(Configuration::getConfigKeyToFullPriceList(), $this->defaultFullPriceListId);
        $this->configManager->flush();
    }
}
