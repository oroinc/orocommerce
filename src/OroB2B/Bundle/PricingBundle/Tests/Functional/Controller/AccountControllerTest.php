<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;


/**
 * @dbIsolation
 */
class AccountControllerTest extends AbstractPriceListsByEntityTestCase
{
    /** @var  Account */
    protected $account;

    public function setUp()
    {
        parent::setUp();
        $this->account = $this->getReference('account.level_1.1.1');
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('orob2b_account_update', ['id' => $this->account->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMainFormName()
    {
        return AccountType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListsByEntity()
    {
        return $this->client
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroB2BPricingBundle:PriceListToAccount')
            ->findBy(['account' => $this->account]);
    }
}
