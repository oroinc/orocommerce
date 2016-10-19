<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Routing\RouterInterface;

/**
 * @dbIsolation
 */
class OrderShippingTrackingControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
        ]);
    }

    public function testChangeAction()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        // open order view page
        $crawler = $this->client->request('GET', $this->getUrl('oro_order_view', ['id' => $order->getId()]));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $shippingTrackingButton = $crawler->filterXPath('//a[@title="Shipping Tracking"]');
        static::assertEquals(1, $shippingTrackingButton->count());

        $updateUrl = $shippingTrackingButton->attr('data-url');
        static::assertNotEmpty($updateUrl);

        // open dialog
        list($route, $parameters) = $this->parseUrl($updateUrl);
        $parameters['_widgetContainer'] = 'dialog';
        $parameters['_wid'] = uniqid('abc', true);

        $crawler = $this->client->request('GET', $this->getUrl($route, $parameters));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();

        // assert new value
        $changeSet[0]= ['method' => 'UPS', 'number' => '1Z9999999'];

        $this->assertOrderShippingTrackingData($form, $changeSet);

        //assert new&update values
        $changeSet[0] = ['method' => 'UPS', 'number' => '1Z11111111'];
        $changeSet[1] = ['method' => 'FedEx', 'number' => '1Z5555555'];

        $this->assertOrderShippingTrackingData($form, $changeSet);

        //assert update&delete values
        $changeSet[0] = ['method' => 'FedEx', 'number' => '1Z5555555'];

        $this->assertOrderShippingTrackingData($form, $changeSet);
    }

    /**
     * @param Form $form
     * @param array $data
     */
    protected function assertOrderShippingTrackingData(Form $form, array $data)
    {
        $values = $form->getPhpValues();
        foreach ($data as $changeSet) {
            $values['oro_order_shipping_tracking_collection'][] = $changeSet;
        }

        $this->client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $repository = $this->getRepository('OroOrderBundle:OrderShippingTracking');

        $trackings = $repository->findBy([
            'order' => $this->getReference(LoadOrders::ORDER_1)
        ]);
        static::assertCount(count($data), $trackings);

        foreach ($data as $value) {
            /** @var OrderShippingTracking|null $tracking */
            $tracking = $repository->findOneBy([
                'order' => $this->getReference(LoadOrders::ORDER_1),
                'method' => $value['method'],
                'number' => $value['number']
            ]);
            static::assertNotNull($tracking);
        }
    }


    /**
     * @param string $url
     * @return array
     */
    protected function parseUrl($url)
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');
        $parameters = $router->match($url);

        $route = $parameters['_route'];
        unset($parameters['_route'], $parameters['_controller']);

        return [$route, $parameters];
    }

    /**
     * @param string $class
     * @return EntityRepository
     */
    protected function getRepository($class)
    {
        return static::getContainer()->get('doctrine')->getManagerForClass($class)->getRepository($class);
    }
}
