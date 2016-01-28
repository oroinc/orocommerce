<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Manager;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Tests\ResultComparatorTrait;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems;

/**
 * @dbIsolation
 */
class TaxManagerTest extends WebTestCase
{
    use ResultComparatorTrait;

    /** @var ConfigManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules',
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadOrderItems',
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxValues',
            ]
        );

        $this->configManager = $this->getContainer()->get('oro_config.global');
    }

    protected function tearDown()
    {
        /** @var EntityRepository $objectRepository */
        $registry = $this->getContainer()->get('doctrine');
        $objectRepository = $registry->getRepository('OroB2BTaxBundle:TaxValue');
        $objectRepository->clear();

        $this->configManager->reset('orob2b_tax.product_prices_include_tax');
        $this->configManager->flush();

        parent::tearDown();
    }

    /**
     * @dataProvider methodsDataProvider
     * @param string $method
     * @param string $reference
     * @param array $expectedResult
     * @param int $expectedQueries
     * @param bool $priceIncludeTax
     * @param array $expectedDatabaseResultBefore
     * @param array $expectedDatabaseResultAfter
     */
    public function testMethods(
        $method,
        $reference,
        array $expectedResult,
        $expectedQueries,
        $priceIncludeTax = false,
        array $expectedDatabaseResultBefore = [],
        array $expectedDatabaseResultAfter = []
    ) {
        $this->configManager->set('orob2b_tax.product_prices_include_tax', $priceIncludeTax);

        $object = $this->getReference($reference);

        $callable = [$this, sprintf('on%sBefore', $method)];
        if (is_callable($callable)) {
            call_user_func($callable, $object, $expectedDatabaseResultBefore);
        }

        $this->executeMethod($method, $object, $expectedResult, $expectedQueries);

        $callable = [$this, sprintf('on%sAfter', $method)];
        if (is_callable($callable)) {
            call_user_func($callable, $object, $expectedDatabaseResultAfter);
        }
    }

    /**
     * @return array
     */
    public function methodsDataProvider()
    {
        return Yaml::parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'tax_manager_test_cases.yml'));
    }

    /**
     * @param string $method
     * @param object $object
     * @param array $expectedResult
     * @param int $expectedQueries
     */
    protected function executeMethod($method, $object, $expectedResult, $expectedQueries)
    {
        $manager = $this->getContainer()->get('orob2b_tax.manager.tax_manager');

        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BTaxBundle:TaxValue');

        $queryAnalyzer = new QueryAnalyzer($em->getConnection()->getDatabasePlatform());

        $prevLogger = $em->getConnection()->getConfiguration()->getSQLLogger();
        $em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        $this->compareResult($expectedResult, $manager->{$method}($object));

//        $this->assertCount(
//            $expectedQueries,
//            $queryAnalyzer->getExecutedQueries(),
//            implode(PHP_EOL, $queryAnalyzer->getExecutedQueries())
//        );

        // cache trigger
        $this->compareResult($expectedResult, $manager->{$method}($object));

//        $this->assertCount(
//            $expectedQueries,
//            $queryAnalyzer->getExecutedQueries(),
//            implode(PHP_EOL, $queryAnalyzer->getExecutedQueries())
//        );

        $em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @param Order|OrderLineItem $object
     * @param array $expectedDatabaseResult
     */
    public function onSaveTaxBefore($object, array $expectedDatabaseResult)
    {
        $this->compareResult(
            $expectedDatabaseResult,
            $this->getContainer()->get('orob2b_tax.manager.tax_manager')->loadTax($object)
        );

        $this->setQuantity($object, 7);
    }

    /**
     * @param Order|OrderLineItem $object
     * @param array $expectedDatabaseResult
     */
    public function onSaveTaxAfter($object, array $expectedDatabaseResult)
    {
        $this->compareResult(
            $expectedDatabaseResult,
            $this->getContainer()->get('orob2b_tax.manager.tax_manager')->loadTax($object)
        );

        $this->setQuantity($object, 6);
    }

    /**
     * @param Order|OrderLineItem $object
     * @param int $quantity
     */
    protected function setQuantity($object, $quantity)
    {
        if ($object instanceof Order && $object->getLineItems()->count()) {
            $object->getLineItems()
                ->filter(
                    function (OrderLineItem $lineItem) {
                        return $lineItem->getProductSku() === LoadOrderItems::ORDER_ITEM_2;
                    }
                )
                ->first()
                ->setQuantity($quantity);
        } elseif ($object instanceof OrderLineItem) {
            $object->setQuantity($quantity);
        }
    }
}
