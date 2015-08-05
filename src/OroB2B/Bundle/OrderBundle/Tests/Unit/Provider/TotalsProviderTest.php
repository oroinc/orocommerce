<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Provider\TotalsProvider;

class TotalsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalsProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->provider = new TotalsProvider($this->translator);
    }

    public function testGetTotals()
    {
        $order = new Order();
        $order->setCurrency('USD');

        $totals = $this->provider->getTotals($order);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $totals);

        $this->assertInstanceOf('OroB2B\Bundle\OrderBundle\Model\Total', $totals->get('subtotal'));
        $this->assertTrue($totals->get('subtotal')->getCurrency() === $order->getCurrency());
    }
}
