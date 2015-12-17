<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\AddressBundle\Entity\Country;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\ZipCode;
use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class LoadTaxDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $rates = [];

    /**
     * @var array
     */
    protected $regionRates = [];

    /**
     * @var array
     */
    protected $codes = [];

    /**
     * @var array
     */
    protected $rows = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $dirPath = $locator->locate('@OroB2BTaxBundle/Migrations/Data/Demo/ORM/tax_rates_data');
        if (is_array($dirPath)) {
            $dirPath = current($dirPath);
        }
        $this->manager = $manager;
        $country = $this->manager->getRepository('OroAddressBundle:Country')->findOneBy(['iso2Code' => 'US']);

        foreach (glob($dirPath . '/*.csv') as $filePath) {
            $handler = fopen($filePath, 'r');
            $headers = fgetcsv($handler, 1000, ',');

            while (($data = fgetcsv($handler, 1000, ',')) !== false) {
                if (count($headers) != count(array_values($data))) {
                    continue;
                }
                $fileRow = array_combine($headers, array_values($data));

                $row = [
                    'state' => $fileRow['State'],
                    'zip' => $fileRow['ZipCode'],
                    'regionName' => $fileRow['TaxRegionName'],
                    'regionCode' => $fileRow['TaxRegionCode'],
                    'rate' => 100 * $fileRow['CombinedRate']
                ];
                $this->rows[] = $row;
                $this->indexRow($row);
            }
            fclose($handler);
        }

        $codes = array_keys($this->regionRates);
        $this->clearIndex();

        $counter = 0;
        foreach ($this->rows as $key => &$row) {
            list($jurisdiction, $productTaxCode, $accountTaxCode) = $this->getJurisdictionAndCodes($row, $country);

            $rule = new TaxRule();
            $rule->setDescription('Rule tax for ' . $row['regionName'] . ' with rate ' . $row['rate'] . '%')
                ->setProductTaxCode($productTaxCode)
                ->setAccountTaxCode($accountTaxCode)
                ->setTaxJurisdiction($jurisdiction)
                ->setTax($this->getTax($row));

            $manager->persist($rule);
            if (++$counter == self::BATCH_SIZE) {
                $manager->flush();
                $counter = 0;
            }
        }
        $manager->flush();
        $manager->clear();

        $products = $this->getProducts($manager);
        foreach ($products as $product) {
            $code = $codes[rand(0, count($codes) - 1)];
            /* @var ProductTaxCode $productTaxCode */
            $productTaxCode = $this->manager
                ->getRepository('OroB2BTaxBundle:ProductTaxCode')
                ->findOneBy(['code' => 'P' . $code]);

            $productTaxCode->addProduct($product);
        }

        $accounts = $this->getAccounts($manager);
        foreach ($accounts as $account) {
            $code = $codes[rand(0, count($codes) - 1)];
            /* @var AccountTaxCode $accountTaxCode */
            $accountTaxCode = $this->manager
                ->getRepository('OroB2BTaxBundle:AccountTaxCode')
                ->findOneBy(['code' => 'A' . $code]);
            $accountTaxCode->addAccount($account);
        }

        $manager->flush();
    }

    /**
     * @param array $row
     * @return ZipCode
     */
    protected function indexRow($row)
    {
        $key = (string)$row['rate'];
        if (isset($this->rates[$key])) {
            $this->rates[$key]['reuses']++;
        } else {
            $this->rates[$key] = ['reuses' => 0];
        }

        $key = $row['regionCode'] . $row['rate'];
        if (isset($this->regionRates[$key])) {
            $this->regionRates[$key]['reuses']++;
        } else {
            $this->regionRates[$key] = ['reuses' => 0];
        }
    }

    protected function clearIndex()
    {
        foreach ($this->rates as $key => $value) {
            if ($value['reuses'] == 0) {
                unset($this->rates[$key]);
            }
        }
        foreach ($this->regionRates as $key => $value) {
            if ($value['reuses'] == 0) {
                unset($this->regionRates[$key]);
            }
        }
    }

    /**
     * @param array $row
     * @return ZipCode
     */
    protected function getZipCode($row)
    {
        $zipCode = new ZipCode();
        $zipCode->setZipCode($row['zip']);

        $this->manager->persist($zipCode);

        return $zipCode;
    }

    /**
     * @param array $row
     * @return Tax
     */
    protected function getTax($row)
    {
        $key = (string)$row['rate'];
        if (isset($this->rates[$key]) && isset($this->rates[$key]['value'])) {
            $tax = $this->rates[$key]['value'];

            if (--$this->rates[$key]['reuses'] == 0) {
                unset($this->rates[$key]);
            }
        } else {
            $tax = new Tax();
            $tax
                ->setCode('TAX' . $key)
                ->setRate($row['rate']);

            $this->manager->persist($tax);

            if (isset($this->rates[$key])) {
                $this->rates[$key]['value'] = $tax;
            }
        }

        return $tax;
    }

    /**
     * @param array $row
     * @param Country $country
     * @return array
     */
    protected function getJurisdictionAndCodes($row, $country)
    {
        $key = $row['regionCode'] . $row['rate'];
        if (isset($this->regionRates[$key]) && isset($this->regionRates[$key]['value'])) {
            $value = $this->regionRates[$key]['value'];
            /** @var TaxJurisdiction $jurisdiction */
            $jurisdiction = $value[0];
            $jurisdiction->addZipCode($this->getZipCode($row));

            if (--$this->regionRates[$key]['reuses'] == 0) {
                unset($this->regionRates[$key]);
            }
        } else {
            $regionName = $row['regionName'];
            $rate = $row['rate'];
            $region = $this->manager
                ->getRepository('OroAddressBundle:Region')
                ->findOneBy(['combinedCode' => $country->getIso2Code() . '-' . $row['state']]);

            $jurisdiction = new TaxJurisdiction();
            $jurisdiction
                ->setCode($key)
                ->setDescription('Tax jurisdiction for ' . $regionName . ' with rate ' . $rate . '%')
                ->setCountry($country)
                ->setRegion($region)
                ->setRegionText($regionName)
                ->addZipCode($this->getZipCode($row));
            $this->manager->persist($jurisdiction);

            $productTaxCode = new ProductTaxCode();
            $productTaxCode
                ->setCode('P' . $key)
                ->setDescription('Product tax for ' . $regionName . ' with rate ' . $rate . '%');
            $this->manager->persist($productTaxCode);

            $accountTaxCode = new AccountTaxCode();
            $accountTaxCode
                ->setCode('A' . $key)
                ->setDescription('Account tax for ' . $regionName . ' with rate ' . $rate . '%');
            $this->manager->persist($accountTaxCode);

            $value = [$jurisdiction, $productTaxCode, $accountTaxCode];
            if (isset($this->regionRates[$key])) {
                $this->regionRates[$key]['value'] = $value;
            }
        }

        return $value;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([], null, 10);

        if (!count($products)) {
            throw new \LogicException('There are no products in system');
        }

        return $products;
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Account[]
     */
    protected function getAccounts(ObjectManager $manager)
    {
        $accounts = $manager->getRepository('OroB2BAccountBundle:Account')->findBy([], null, 10);

        if (!count($accounts)) {
            throw new \LogicException('There are no accounts in system');
        }

        return $accounts;
    }
}
