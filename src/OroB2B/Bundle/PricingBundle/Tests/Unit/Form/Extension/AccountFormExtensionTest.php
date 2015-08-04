<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Extension\AccountFormExtension;

class AccountFormExtensionTest extends AbstractPriceListExtensionTest
{
    /**
     * @return AccountFormExtension
     */
    protected function getExtension()
    {
        return new AccountFormExtension($this->registry);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSetDataProvider()
    {
        return [
            [null, false],
            [new Account(), false],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1), true],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1), true],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1), true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmitDataProvider()
    {
        return [
            [null, false],
            [new Account(), false],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1), true],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1), false, false],
            [$this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1), true, true, new PriceList()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getGetterMethodName()
    {
        return 'getPriceListByAccount';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSetterMethodName()
    {
        return 'setPriceListToAccount';
    }

    /**
     * {@inheritdoc}
     */
    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->getExtension()->getExtendedType());
        $this->assertEquals('orob2b_account_type', $this->getExtension()->getExtendedType());
    }
}
