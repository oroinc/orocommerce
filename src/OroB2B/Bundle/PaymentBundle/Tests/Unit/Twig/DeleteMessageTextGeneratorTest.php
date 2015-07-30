<?php

namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Twig;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Twig\DeleteMessageTextGenerator;
use OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Fixtures\PaymentTermStub;

class DeleteMessageTextGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeleteMessageTextGenerator
     */
    protected $extension;

    /** @var RouterInterface */
    protected $router;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    protected function setUp()
    {
        $this->router = $this->getMock('\Symfony\Component\Routing\RouterInterface');
        $twig = $this->getMock('\Oro\Bundle\UIBundle\Twig\Environment');

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function($route, $params) {
                return serialize($params);
            });

        $twig->expects($this->any())
            ->method('render')
            ->willReturnCallback(function($template, $params) {
                return serialize($params);
            });

        $this->managerRegistry = $this->getMockBuilder('\Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DeleteMessageTextGenerator($this->router, $twig, $this->managerRegistry);
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @dataProvider getDeleteMessageTextDataProvider
     */
    public function testGetDeleteMessageText(PaymentTerm $paymentTerm)
    {
        $message = $this->extension->getDeleteMessageText($paymentTerm);
        $this->assertDeleteMessage(
            $message,
            $paymentTerm->getId(),
            $paymentTerm->getCustomerGroups()->count(),
            $paymentTerm->getCustomers()->count()
        );
    }
    
    public function testGetDeleteMessageTextForDataGrid()
    {
        $paymentTermId = 1;

        $paymentTermRepository = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTermRepository->expects($this->once())
            ->method('find')
            ->with($paymentTermId)
            ->willReturnCallback(function($id) {
                return new PaymentTermStub($id);
            });

        $om = $this->getMock('\Doctrine\Common\Persistence\ObjectManager');
        $om->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OroB2BPaymentBundle:PaymentTerm'))
            ->willReturn($paymentTermRepository);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->equalTo('OroB2BPaymentBundle:PaymentTerm'))
            ->willReturn($om);



        $message = $this->extension->getDeleteMessageTextForDataGrid($paymentTermId);
        $this->assertDeleteMessage(
            $message,
            $paymentTermId,
            0,
            0
        );
    }

    /**
     * @param $message
     * @param $paymentTermId
     * @param $customerGroupCount
     * @param $customerCount
     */
    public function assertDeleteMessage($message, $paymentTermId, $customerGroupCount, $customerCount)
    {
        $message = unserialize($message);

        $this->assertArrayHasKey('customerGroupFilterUrl', $message);
        $this->assertArrayHasKey('customerFilterUrl', $message);

        if ($customerGroupCount) {
            $customerGroupFilterUrl = unserialize($message['customerGroupFilterUrl']);
            $this->assertDataFromDeleteMessage(
                $message,
                $paymentTermId,
                $customerGroupFilterUrl,
                DeleteMessageTextGenerator::CUSTOMER_GROUP_GRID_NAME,
                'orob2b.customer.customergroup.entity_label'
            );
        } else {
            $this->assertNull($message['customerGroupFilterUrl']);
        }

        if ($customerCount) {
            $customerFilterUrl = unserialize($message['customerFilterUrl']);
            $this->assertDataFromDeleteMessage(
                $message,
                $paymentTermId,
                $customerFilterUrl,
                DeleteMessageTextGenerator::CUSTOMER_GRID_NAME,
                'orob2b.customer.entity_label'
                );
        } else {
            $this->assertNull($message['customerFilterUrl']);
        }
    }

    /**
     * @return array
     */
    public function getDeleteMessageTextDataProvider()
    {
        return [
            'payment with customer and customer group' => [
                'paymentTerm' => new PaymentTermStub(1, ['one customer'], ['one customerGroup'])
            ],
            'payment only with customer' => [
                'paymentTerm' => new PaymentTermStub(1, ['one customer'], [])
            ],
            'payment only with customer group' => [
                'paymentTerm' => new PaymentTermStub(1, [], ['one customerGroup'])
            ],
            'empty' => [
                'paymentTerm' => new PaymentTermStub(1, [], [])
            ],
        ];
    }

    /**
     * @param $gridName
     * @param $paymentTermId
     * @return array
     */
    private function generateUrlParameters($gridName, $paymentTermId) {
        return [
            $gridName => [
                OrmFilterExtension::FILTER_ROOT_PARAM => [
                    'payment_term_label' => [
                        'value' => $paymentTermId
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $message
     * @param $paymentTerm
     */
    private function assertDataFromDeleteMessage($message, $paymentTermId, $customerFilterUrl, $gridName, $label)
    {
        $urlParameters = unserialize($customerFilterUrl['urlPath']);

        $this->assertEquals(
            $this->generateUrlParameters(
                $gridName,
                $paymentTermId
            ),
            $urlParameters
        );

        $this->assertEquals($label, $customerFilterUrl['label']);
    }
}