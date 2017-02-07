<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\Testing\Unit\EntityTrait;

class DPDRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /** @var DPDRequestFactory */
    protected $dpdRequestFactory;

    protected function setUp()
    {
        $this->transport = $this->getEntity(
            DPDTransport::class,
            [
                'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
                'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
            ]
        );

        $this->shippingService = $this->getEntity(
            ShippingService::class,
            [
                'code' => 'Classic',
                'description' => 'DPD Classic',
            ]
        );

        $this->dpdRequestFactory = $this->createMock(DPDRequestFactory::class);

        $this->dpdRequestFactory = new DPDRequestFactory();
    }

    /**
     * @param $requestAction
     * @param $shipDate
     * @param $orderId
     * @param $orderAddress
     * @param $orderEmail
     * @param $packages
     *
     * @dataProvider testCreateSetOrderRequestDataProvider
     */
    public function testCreateSetOrderRequest(
        $requestAction,
        $shipDate,
        $orderId,
        $orderAddress,
        $orderEmail,
        $packages
    ) {
        $request = $this->dpdRequestFactory->createSetOrderRequest(
            $this->transport,
            $this->shippingService,
            $requestAction,
            $shipDate,
            $orderId,
            $orderAddress,
            $orderEmail,
            $packages
        );
        static::assertInstanceOf(SetOrderRequest::class, $request);
        static::assertEquals(count($packages), count($request->getOrderDataList()));
        static::assertEquals($requestAction, $request->getOrderAction());
        static::assertEquals($shipDate, $request->getShipDate());
        if (count($request->getOrderDataList()) > 0) {
            /** @var OrderData $orderData */
            foreach ($request->getOrderDataList() as $idx => $orderData) {
                static::assertEquals($packages[$idx]->getContents(), $orderData->getReference1());
                static::assertEquals($orderId, $orderData->getReference2());
            }
        }
    }

    public function testCreateSetOrderRequestDataProvider()
    {
        return [
            'no_packages' => [
                'requestAction' => SetOrderRequest::START_ORDER_ACTION,
                'shipDate' => new \DateTime(),
                'orderId' => '1',
                'orderAddress' => new OrderAddress(),
                'orderEmail' => 'an@email.com',
                'packages' => [],
            ],
            'one_packages' => [
                'requestAction' => SetOrderRequest::START_ORDER_ACTION,
                'shipDate' => new \DateTime(),
                'orderId' => '1',
                'orderAddress' => new OrderAddress(),
                'orderEmail' => 'an@email.com',
                'packages' => [(new Package())->setContents('contents')],
            ],
            'two_packages' => [
                'requestAction' => SetOrderRequest::START_ORDER_ACTION,
                'shipDate' => new \DateTime(),
                'orderId' => '1',
                'orderAddress' => new OrderAddress(),
                'orderEmail' => 'an@email.com',
                'packages' => [(new Package())->setContents('contents'),
                    (new Package())->setContents('other contents')],
            ],
        ];
    }
}
