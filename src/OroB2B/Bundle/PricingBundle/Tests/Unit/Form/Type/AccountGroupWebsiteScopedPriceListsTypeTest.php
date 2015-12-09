<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Form\Type\AccountGroupWebsiteScopedPriceListsType;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

class AccountGroupWebsiteScopedPriceListsTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AccountGroupWebsiteScopedPriceListsType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new AccountGroupWebsiteScopedPriceListsType($registry);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals(AccountGroupWebsiteScopedPriceListsType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(WebsiteScopedDataType::NAME, $this->formType->getParent());
    }
}
