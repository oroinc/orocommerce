<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Extension\AccountGroupFormExtension;

class AccountGroupFormExtensionTest extends AbstractPriceListExtensionTest
{
    /**
     * @return AccountGroupFormExtension
     */
    protected function getExtension()
    {
        return new AccountGroupFormExtension($this->registry);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetDataProvider()
    {
        return [
            [null, false],
            [new AccountGroup(), false],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1), true],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1), true],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1), true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmitDataProvider()
    {
        return [
            [null, false],
            [new AccountGroup(), false],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1), true],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1), false, false],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1), true, true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getGetterMethodName()
    {
        return 'getPriceListByAccountGroup';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetterMethodName()
    {
        return 'setPriceListToAccountGroup';
    }

    /**
     * {@inheritdoc}
     */
    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->getExtension()->getExtendedType());
        $this->assertEquals('orob2b_account_group_type', $this->getExtension()->getExtendedType());
    }
}
