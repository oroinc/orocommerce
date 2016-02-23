<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Form\Type\AccountGroupWebsiteScopedPriceListsType;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AccountGroupWebsiteScopedPriceListsTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AccountGroupWebsiteScopedPriceListsType */
    protected $formType;

    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new AccountGroupWebsiteScopedPriceListsType($registry, $this->getEventDispatcher());
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        }

        return $this->eventDispatcher;
    }
}
