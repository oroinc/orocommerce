<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Migrations\ZipCodeRangeHelper;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountGroupTaxCode;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class LoadTaxDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    const BATCH_SIZE = 2000;
    const DEFAULT_COUNTRY = 'US';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string[]
     */
    protected $taxes = [];

    /**
     * @var string[]
     */
    protected $jurisdictions = [];

    /**
     * @var string[]
     */
    protected $productTaxCodes = [];

    /**
     * @var string[]
     */
    protected $accountTaxCodes = [];

    /**
     * @var string[]
     */
    protected $accountGroupTaxCodes = [];

    /**
     * @var string
     */
    protected $currentTime;

    /**
     * @var string[][]
     */
    protected $scheduledZipCodes = [];

    /**
     * @var string[][]
     */
    protected $scheduledTaxRules = [];

    /**
     * @var ZipCodeRangeHelper
     */
    protected $helper;

    /** {@inheritdoc} */
    public function __construct()
    {
        $this->helper = new ZipCodeRangeHelper();
    }

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
            'OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     * @param EntityManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->connection = $manager->getConnection();
        $this->connection->getConfiguration()->setSQLLogger(null);
        $this->connection->setAutoCommit(false);

        $this->readFiles('@OroB2BTaxBundle/Migrations/Data/Demo/ORM/tax_rates_data');
        $this->handleScheduledZipCodes();
        $this->handleScheduledTaxRules();

        $this->connection->commit();

        $this->fillProducts($manager);
        $this->fillAccounts($manager);
        $this->fillAccountGroups($manager);

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param string $path
     */
    protected function readFiles($path)
    {
        $locator = $this->container->get('file_locator');
        $dirPath = $locator->locate($path);
        if (is_array($dirPath)) {
            $dirPath = current($dirPath);
        }

        foreach (glob($dirPath . '/*.csv') as $filePath) {
            $handler = fopen($filePath, 'r');
            $headers = fgetcsv($handler, 1000, ',');
            while (($data = fgetcsv($handler, 1000, ',')) !== false) {
                if (count($headers) != count(array_values($data))) {
                    continue;
                }
                $fileRow = array_combine($headers, array_values($data));
                $this->handle($fileRow);
            }
            fclose($handler);
        }
    }

    /**
     * @param EntityManager $manager
     */
    protected function fillProducts(EntityManager $manager)
    {
        $products = $this->getProducts($manager);
        foreach ($products as $product) {
            $id = $this->productTaxCodes[array_rand($this->productTaxCodes)];
            /* @var ProductTaxCode $productTaxCode */
            $productTaxCode = $manager
                ->getReference('OroB2BTaxBundle:ProductTaxCode', $id);

            $productTaxCode->addProduct($product);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Product[]
     */
    protected function getProducts(ObjectManager $manager)
    {
        $products = $manager->getRepository('OroB2BProductBundle:Product')->findBy([]);

        return $products;
    }

    /**
     * @param EntityManager $manager
     */
    private function fillAccounts(EntityManager $manager)
    {
        $accounts = $this->getAccounts($manager);
        foreach ($accounts as $account) {
            $id = $this->accountTaxCodes[array_rand($this->accountTaxCodes)];
            /* @var AccountTaxCode $accountTaxCode */
            $accountTaxCode = $manager
                ->getReference('OroB2BTaxBundle:AccountTaxCode', $id);

            $accountTaxCode->addAccount($account);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|Account[]
     */
    protected function getAccounts(ObjectManager $manager)
    {
        $accounts = $manager->getRepository('OroB2BAccountBundle:Account')->findBy([]);

        return $accounts;
    }


    /**
     * @param EntityManager $manager
     */
    private function fillAccountGroups(EntityManager $manager)
    {
        $accountGroups = $this->getAccountGroups($manager);
        foreach ($accountGroups as $accountGroup) {
            $id = $this->accountGroupTaxCodes[array_rand($this->accountGroupTaxCodes)];
            /* @var AccountGroupTaxCode $accountTaxCode */
            $accountTaxCode = $manager
                ->getReference('OroB2BTaxBundle:AccountTaxCode', $id);

            $accountTaxCode->addAccountGroup($accountGroup);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return Collection|AccountGroup[]
     */
    protected function getAccountGroups(ObjectManager $manager)
    {
        $accountGroups = $manager->getRepository('OroB2BAccountBundle:AccountGroup')->findBy([]);

        return $accountGroups;
    }

    /**
     * @param array $row
     */
    protected function handle($row)
    {
        $jurisdictionId = $this->createTaxJurisdiction($row);
        if (false !== $jurisdictionId) {
            $this->scheduleAddZipCode($jurisdictionId, $row['ZipCode']);
        }
    }

    /**
     * @param string $code
     * @param string $rate
     * @return string
     */
    protected function createTax($code, $rate)
    {
        if (!array_key_exists($code, $this->taxes)) {
            $this->connection->insert(
                'orob2b_tax',
                [
                    'code' => $code,
                    'description' => sprintf('%s tax with rate %s%%', $code, $rate * 100),
                    'rate' => $rate,
                    'created_at' => $this->getCurrentTime(),
                    'updated_at' => $this->getCurrentTime(),
                ]
            );

            $this->taxes[$code] = $this->connection->lastInsertId('orob2b_tax_id_seq');
        }

        return $this->taxes[$code];
    }

    /**
     * @param $row
     * @return string|bool false if wasn't created
     */
    protected function createTaxJurisdiction($row)
    {
        $rate = $row['CombinedRate'];
        if ($rate == 0) {
            return false;
        }

        $code = $row['TaxRegionCode'];
        if (!array_key_exists($code, $this->jurisdictions)) {
            $this->connection->insert(
                'orob2b_tax_jurisdiction',
                [
                    'code' => $code,
                    'description' => sprintf('%s %s %s Jurisdiction', self::DEFAULT_COUNTRY, $row['State'], $code),
                    'country_code' => self::DEFAULT_COUNTRY,
                    'region_code' => sprintf('%s-%s', self::DEFAULT_COUNTRY, $row['State']),
                    'created_at' => $this->getCurrentTime(),
                    'updated_at' => $this->getCurrentTime(),
                ]
            );

            $this->jurisdictions[$code] = $this->connection->lastInsertId('orob2b_tax_jurisdiction_id_seq');

            $taxId = $this->createTax($row['TaxRegionCode'], $rate);
            $this->scheduleTaxRule($row, $taxId, $this->jurisdictions[$code]);
        }

        return $this->jurisdictions[$code];
    }

    /**
     * @param $jurisdictionId
     * @param $zipCode
     */
    protected function scheduleAddZipCode($jurisdictionId, $zipCode)
    {
        $this->scheduledZipCodes[$jurisdictionId][] = $zipCode;
    }

    protected function handleScheduledZipCodes()
    {
        $data = [];
        $time = $this->getCurrentTime();

        foreach ($this->scheduledZipCodes as $jurisdictionId => $zipCodes) {
            $this->helper->extractZipCodeRanges($data, $zipCodes, $jurisdictionId, $time);


            if (count($data) > self::BATCH_SIZE) {
                $this->insertZipCodes($data);
                $data = [];
            }
        }

        $this->insertZipCodes($data);
        $this->scheduledZipCodes = [];
    }

    /**
     * @param array $data
     * @return int
     */
    protected function insertZipCodes($data)
    {
        return $this->batchInsert(
            'orob2b_tax_zip_code',
            [
                'tax_jurisdiction_id',
                'zip_code',
                'zip_range_start',
                'zip_range_end',
                'created_at',
                'updated_at',
            ],
            $data
        );
    }

    /**
     * @param array $row
     * @param string $taxId
     * @param string $jurisdictionId
     * @return string
     */
    protected function scheduleTaxRule($row, $taxId, $jurisdictionId)
    {
        $regionCode = $row['TaxRegionCode'];
        $normalizedRate = $row['CombinedRate'] * 100;

        $productTaxCodeId = $this->createProductTaxCode($regionCode, $normalizedRate);
        $accountTaxCodeId = $this->createAccountTaxCode($regionCode, $normalizedRate);
        $accountGroupTaxCodeId = $this->createAccountGroupTaxCode($regionCode, $normalizedRate);

        $this->scheduledTaxRules[] = [
            'product_tax_code_id' => $productTaxCodeId,
            'account_tax_code_id' => $accountTaxCodeId,
            'account_group_tax_code_id' => $accountGroupTaxCodeId,
            'tax_id' => $taxId,
            'tax_jurisdiction_id' => $jurisdictionId,
            'description' => sprintf('Tax rule for %s with rate %s%%', $regionCode, $normalizedRate),
            'created_at' => $this->getCurrentTime(),
            'updated_at' => $this->getCurrentTime(),
        ];
    }

    protected function handleScheduledTaxRules()
    {
        $data = [];
        foreach ($this->scheduledTaxRules as $scheduledTaxRule) {
            $data[] = array_values($scheduledTaxRule);

            if (count($data) > self::BATCH_SIZE) {
                $this->insertTaxRules($data);
                $data = [];
            }
        }

        $this->insertTaxRules($data);
        $this->scheduledTaxRules = [];
    }

    /**
     * @param array $data
     * @return int
     */
    protected function insertTaxRules($data)
    {
        return $this->batchInsert(
            'orob2b_tax_rule',
            [
                'product_tax_code_id',
                'account_tax_code_id',
                'account_group_tax_code_id',
                'tax_id',
                'tax_jurisdiction_id',
                'description',
                'created_at',
                'updated_at',
            ],
            $data
        );
    }

    /**
     * @param string $regionCode
     * @param float $taxRate
     * @return string
     */
    protected function createProductTaxCode($regionCode, $taxRate)
    {
        $key = $regionCode;
        if (!array_key_exists($key, $this->productTaxCodes)) {
            $this->connection->insert(
                'orob2b_tax_product_tax_code',
                [
                    'code' => $regionCode,
                    'description' => sprintf('Product tax code for %s with rate %u%%', $regionCode, $taxRate),
                    'created_at' => $this->getCurrentTime(),
                    'updated_at' => $this->getCurrentTime(),
                ]
            );

            $this->productTaxCodes[$key] = $this->connection->lastInsertId('orob2b_tax_product_tax_code_id_seq');
        }

        return $this->productTaxCodes[$key];
    }

    /**
     * @param string $regionCode
     * @param float $taxRate
     * @return string
     */
    protected function createAccountTaxCode($regionCode, $taxRate)
    {
        $key = $regionCode;
        if (!array_key_exists($key, $this->accountTaxCodes)) {
            $this->connection->insert(
                'orob2b_tax_account_tax_code',
                [
                    'code' => $regionCode,
                    'description' => sprintf('Account tax code for %s with rate %s%%', $regionCode, $taxRate),
                    'created_at' => $this->getCurrentTime(),
                    'updated_at' => $this->getCurrentTime(),
                ]
            );

            $this->accountTaxCodes[$key] = $this->connection->lastInsertId('orob2b_tax_account_tax_code_id_seq');
        }

        return $this->accountTaxCodes[$key];
    }


    /**
     * @param string $regionCode
     * @param float $taxRate
     * @return string
     */
    protected function createAccountGroupTaxCode($regionCode, $taxRate)
    {
        $key = $regionCode;
        if (!array_key_exists($key, $this->accountGroupTaxCodes)) {
            $this->connection->insert(
                'orob2b_tax_acc_group_tax_code',
                [
                    'code' => $regionCode,
                    'description' => sprintf('Account group tax code for %s with rate %s%%', $regionCode, $taxRate),
                    'created_at' => $this->getCurrentTime(),
                    'updated_at' => $this->getCurrentTime(),
                ]
            );

            $this->accountGroupTaxCodes[$key] = $this->connection->lastInsertId('orob2b_tax_acc_group_tax_code_id_seq');
        }

        return $this->accountGroupTaxCodes[$key];
    }

    /**
     * @return string
     */
    protected function getCurrentTime()
    {
        if (null === $this->currentTime) {
            $this->currentTime = date("Y-m-d H:i:s");
        }

        return $this->currentTime;
    }

    /**
     * This method is modified version of @see \Doctrine\DBAL\Connection::insert for batch inserts
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array $columns Array of affected columns
     * @param array $data An associative array containing column-value pairs.
     * @return int The number of affected rows.
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function batchInsert($tableExpression, $columns, $data)
    {
        if (empty($data)) {
            return 0;
        }

        $placeholders = [];
        $params = [];

        foreach ($data as $record) {
            $placeholders[] = implode(', ', array_fill(0, count($record), '?'));
            foreach ($record as $value) {
                $params[] = $value;
            }
        }

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $tableExpression,
            implode(', ', $columns),
            implode('), (', $placeholders)
        );

        return $this->connection->executeUpdate($query, $params);
    }
}
