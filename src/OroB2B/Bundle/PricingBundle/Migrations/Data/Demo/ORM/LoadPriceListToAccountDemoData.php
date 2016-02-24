<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;

class LoadPriceListToAccountDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BPricingBundle/Migrations/Data/Demo/ORM/data/price_lists_to_account.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            /** @var EntityManager $manager */
            $account = $this->getAccountByName($manager, $row['account']);
            $priceList = $this->getPriceListByName($manager, $row['priceList']);
            $website = $this->getWebsiteByName($manager, $row['website']);

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
        $website = $manager->getRepository('OroB2BAccountBundle:Account')->findOneBy(['name' => $name]);

        if (!$website) {
            throw new \LogicException(sprintf('There is no account with name "%s" .', $name));
        }

        return $website;
    }
}
