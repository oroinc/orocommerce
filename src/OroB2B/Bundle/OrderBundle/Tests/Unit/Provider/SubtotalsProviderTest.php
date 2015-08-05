<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalsProvider;

class SubtotalsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SubtotalsProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->provider = new SubtotalsProvider($this->translator);
    }

    public function testGetSubtotals()
    {
        $order = new Order();
        $order->setCurrency('USD');

        $subtotals = $this->provider->getSubtotals($order);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $subtotals);

        $subtotal = $subtotals->get(Subtotal::TYPE_SUBTOTAL);
        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Subtotal', $subtotal);
        $this->assertEquals(Subtotal::TYPE_SUBTOTAL, $subtotal->getType());
        $this->assertEquals($order->getCurrency(), $subtotal->getCurrency());
        $this->assertTrue(0 === $subtotal->getAmount());
    }
}
