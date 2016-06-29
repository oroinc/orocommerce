<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;

/**
 * @dbIsolation
 * @group CommunityEdition
 */
class AccountGroupControllerTest extends AbstractPriceListsByEntityTestCase
{
    /**
     * @var  AccountGroup
     */
    protected $accountGroup;

    public function setUp()
    {
        parent::setUp();
        $this->accountGroup = $this->getReference('account_group.group3');
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateUrl($id = null)
    {
        return $this->getUrl('orob2b_account_group_update', ['id' => $id ?: $this->accountGroup->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateUrl()
    {
        return $this->getUrl('orob2b_account_group_create');
    }

    /**
     * {@inheritdoc}
     */
    public function getViewUrl()
    {
        return $this->getUrl('orob2b_account_group_view', ['id' => $this->accountGroup->getId()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMainFormName()
    {
        return AccountGroupType::NAME;
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
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->findBy(['accountGroup' => $this->accountGroup]);
    }
}
