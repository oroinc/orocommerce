<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Entity\PriceListToAccount;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

class LoadPriceListToAccountDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * @var EntityRepository
     */
    protected $accountRepository;

    /**
     * @var Account[]
     */
    protected $accounts = [];

    /**
     * {@inheritdoc}
     * @param EntityManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists_to_account.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        /** @var EntityManager $manager */
        $website = $this->getWebsiteByName($manager, LoadWebsiteData::DEFAULT_WEBSITE_NAME);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $account = $this->getAccountByName($manager, $row['account']);
            $priceList = $this->getPriceListByName($manager, $row['priceList']);

            $priceListToAccount = new PriceListToAccount();
            $priceListToAccount->setAccount($account)
                ->setPriceList($priceList)
                ->setWebsite($website)
                ->setPriority($row['priority'])
                ->setMergeAllowed((boolean)$row['mergeAllowed']);

            $manager->persist($priceListToAccount);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Account
     */
    protected function getAccountByName(EntityManager $manager, $name)
    {
        if (!array_key_exists($name, $this->accounts)) {
            $account = $this->getAccountRepository($manager)->findOneBy(['name' => $name]);

            if (!$account) {
                throw new \LogicException(sprintf('There is no account with name "%s" .', $name));
            }
            $this->accounts[$name] = $account;
        }


        return $this->accounts[$name];
    }

    /**
     * @param $manager
     * @return EntityRepository
     */
    protected function getAccountRepository(EntityManager $manager)
    {
        if ($this->accountRepository === null) {
            $this->accountRepository = $manager->getRepository('OroCustomerBundle:Account');
        }

        return $this->accountRepository;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(parent::getDependencies(), [LoadWebsiteData::class]);
    }
}
