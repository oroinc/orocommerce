<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;

/**
 * @dbIsolation
 * @group CommunityEdition
 */
class AccountControllerTest extends AbstractPriceListsByEntityTestCase
{
    /**
     * @var  Account
     */
    protected $account;

    public function setUp()
    {
        parent::setUp();
        $this->account = $this->getReference('account.level_1_1');
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateUrl($id = null)
    {
        return $this->getUrl('orob2b_account_update', ['id' => $id ?: $this->account->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateUrl()
    {
        return $this->getUrl('orob2b_account_create');
    }

    /**
     * {@inheritdoc}
     */
    public function getViewUrl()
    {
        return $this->getUrl('orob2b_account_view', ['id' => $this->account->getId()]);
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
