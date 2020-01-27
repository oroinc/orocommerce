<?php

namespace Oro\Bundle\PricingBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\FormBundle\Tests\Behat\Element\Select2Entity;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListIdentifierProviderInterface;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\ProductBundle\Tests\Behat\Element\ProductPriceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;

class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    /**
     * @Then /^(?:|I )set (?P<collectionFieldName>[^"]+) collection element values in (?P<number>\d+) row:$/
     *
     * @param string $collectionFieldName
     * @param int $number
     * @param TableNode $table
     */
    public function changeCollectionElement($collectionFieldName, $number, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('OroForm');

        /** @var ProductPriceCollection $collection */
        $collection = $form->findField($collectionFieldName);

        $collection->changeRow($number, $table->getRowsHash());
    }

    /**
     * @Then /^(?:|I )should see following data for (?P<collectionFieldName>[^"]+) collection:$/
     *
     * @param string $collectionFieldName
     * @param TableNode $table
     */
    public function assertProductPriceValues($collectionFieldName, TableNode $table)
    {
        /** @var Form $form */
        $form = $this->createElement('OroForm');

        /** @var ProductPriceCollection $collection */
        $collection = $form->findField($collectionFieldName);

        $collection->assertRows($table->getColumnsHash());
    }

    /**
     * @Then /^(?:|I )select price list with name "(?P<name>[\w\d\s]+)" on sidebar$/
     *
     * @param string $name
     */
    public function selectPriceListWithNameOnSidebar($name)
    {
        /** @var Select2Entity $input */
        $input = $this->createElement('PriceListSidebarSelector');
        $input->setValue($name);
    }

    /**
     * @Then /^There are (?P<count>[\d]+) prices in combined price list:$/
     *
     * @param int $count
     * @param TableNode $table
     */
    public function combinedPriceListPricesCount($count, TableNode $table)
    {
        $em = $this->getKernel()->getContainer()->get('doctrine')->getManagerForClass(CombinedPriceList::class);

        $priceLists = [];
        /** @var PriceListRepository $plRepo */
        $plRepo = $em->getRepository(PriceList::class);
        foreach ($table->getRows() as $row) {
            /** @var PriceList $priceList */
            $priceList = $plRepo->findOneBy(['name' => $row[0]]);
            $priceLists[] = new PriceListSequenceMember($priceList, true);
        }
        $identifier = $this->getCplIdentifier($priceLists);

        /** @var CombinedPriceListRepository $repo */
        $cplRepo = $em->getRepository(CombinedPriceList::class);
        $cpl = $cplRepo->findOneBy(['name' => $identifier]);

        $this->assertNotNull($cpl);

        /** @var CombinedProductPriceRepository $cplPriceRepo */
        $cplPriceRepo = $em->getRepository(CombinedProductPrice::class);
        $prices = $cplPriceRepo->findBy(['priceList' => $cpl]);

        $this->assertCount((int)$count, $prices);
    }

    /**
     * @param array|PriceListSequenceMember[] $priceLists
     * @return string
     */
    private function getCplIdentifier(array $priceLists): string
    {
        $strategy = $this
            ->getKernel()
            ->getContainer()
            ->get('oro_pricing.pricing_strategy.strategy_register')
            ->getCurrentStrategy();

        if ($strategy instanceof CombinedPriceListIdentifierProviderInterface) {
            return $strategy->getCombinedPriceListIdentifier($priceLists);
        }

        $map = [false => 'f', true => 't'];
        $idParts = array_map(static function (PriceListSequenceMember $member) use ($map) {
            return $member->getPriceList()->getId() . $map[$member->isMergeAllowed()];
        }, $priceLists);

        return md5(implode('_', $idParts));
    }
}
