<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Functional\Form\DataFixtures\OrderAddressUpdateFixture;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderAddressTypeDBConsistencyTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            OrderAddressUpdateFixture::class,
        ]);
    }

    public function testSubmitOrderAddress(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        /** @var Order $order */
        $order = $this->getReference(OrderAddressUpdateFixture::ORDER_NAME);

        /** @var OrderAddress $originalShippingAddress */
        $originalShippingAddress = $order->getShippingAddress();

        /** @var CustomerUserAddress $newCustomerUserAddress */
        $newCustomerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.user_address');

        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl(
                'oro_order_update',
                ['id' => $order->getId()]
            )
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_order_type[shippingAddress][customerAddress]' => self::getContainer()
                    ->get('oro_order.manager.order_address')
                    ->getIdentifier($newCustomerUserAddress),
                'oro_order_type[shippingAddress][label]' => $newCustomerUserAddress->getLabel(),
                'oro_order_type[shippingAddress][country]' => $newCustomerUserAddress->getCountryIso2(),
                'oro_order_type[shippingAddress][region]' =>
                    $newCustomerUserAddress->getCountryIso2() . '-' . $newCustomerUserAddress->getRegionCode(),
                'oro_order_type[shippingAddress][city]' => $newCustomerUserAddress->getCity(),
                'oro_order_type[shippingAddress][street]' => $newCustomerUserAddress->getStreet(),
                'oro_order_type[shippingAddress][postalCode]' => $newCustomerUserAddress->getPostalCode(),
            ]
        );
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $redirectAction]);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Order $updatedOrder */
        $updatedOrder = $entityManager
            ->getRepository(Order::class)
            ->find($order->getId());

        $updatedShippingAddress = $updatedOrder->getShippingAddress();
        self::assertNotNull($updatedShippingAddress);
        self::assertSame($originalShippingAddress->getId(), $updatedShippingAddress->getId());
        self::assertSame($newCustomerUserAddress->getLabel(), $updatedShippingAddress->getLabel());
        self::assertSame($newCustomerUserAddress->getCountryName(), $updatedShippingAddress->getCountryName());
        self::assertSame($newCustomerUserAddress->getRegionCode(), $updatedShippingAddress->getRegionCode());
        self::assertSame($newCustomerUserAddress->getStreet(), $updatedShippingAddress->getStreet());
        self::assertSame($newCustomerUserAddress->getPostalCode(), $updatedShippingAddress->getPostalCode());
        self::assertSame(
            $newCustomerUserAddress->getId(),
            $updatedShippingAddress->getCustomerUserAddress()->getId()
        );

        $allOrderAddresses = $entityManager->getRepository(OrderAddress::class)->findAll();
        self::assertCount(2, $allOrderAddresses);
    }

    public function testSubmitOrderAddressWhenNull(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        /** @var Order $order */
        $order = $this->getReference(OrderAddressUpdateFixture::ORDER_NAME);

        /** @var OrderAddress $originalShippingAddress */
        $originalShippingAddress = $order->getShippingAddress();

        /** @var CustomerUserAddress $newCustomerUserAddress */
        $newCustomerUserAddress = $this->getReference('grzegorz.brzeczyszczykiewicz@example.com.user_address');

        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl(
                'oro_order_update',
                ['id' => $order->getId()]
            )
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_order_type[shippingAddress][customerAddress]' => '',
                'oro_order_type[shippingAddress][label]' => $newCustomerUserAddress->getLabel(),
                'oro_order_type[shippingAddress][country]' => $newCustomerUserAddress->getCountryIso2(),
                'oro_order_type[shippingAddress][region]' =>
                    $newCustomerUserAddress->getCountryIso2() . '-' . $newCustomerUserAddress->getRegionCode(),
                'oro_order_type[shippingAddress][city]' => $newCustomerUserAddress->getCity(),
                'oro_order_type[shippingAddress][street]' => $newCustomerUserAddress->getStreet(),
                'oro_order_type[shippingAddress][postalCode]' => $newCustomerUserAddress->getPostalCode(),
            ]
        );
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $redirectAction]);

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Order $updatedOrder */
        $updatedOrder = $entityManager
            ->getRepository(Order::class)
            ->find($order->getId());

        $updatedShippingAddress = $updatedOrder->getShippingAddress();
        self::assertNotNull($updatedShippingAddress);
        self::assertSame($originalShippingAddress->getId(), $updatedShippingAddress->getId());
        self::assertSame($newCustomerUserAddress->getLabel(), $updatedShippingAddress->getLabel());
        self::assertSame($newCustomerUserAddress->getCountryName(), $updatedShippingAddress->getCountryName());
        self::assertSame($newCustomerUserAddress->getRegionCode(), $updatedShippingAddress->getRegionCode());
        self::assertSame($newCustomerUserAddress->getStreet(), $updatedShippingAddress->getStreet());
        self::assertSame($newCustomerUserAddress->getPostalCode(), $updatedShippingAddress->getPostalCode());
        self::assertNull($updatedShippingAddress->getCustomerUserAddress());

        $allOrderAddresses = $entityManager->getRepository(OrderAddress::class)->findAll();
        self::assertCount(2, $allOrderAddresses);
    }
}
