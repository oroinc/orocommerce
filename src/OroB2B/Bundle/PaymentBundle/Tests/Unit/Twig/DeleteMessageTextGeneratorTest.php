<?php

namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Twig;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Twig\DeleteMessageTextGenerator;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Fixtures\Stub\PaymentTermStub;

class DeleteMessageTextGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var DeleteMessageTextGenerator */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $managerRegistry;

    protected function setUp()
    {
        $this->router = $this->getMock('\Symfony\Component\Routing\RouterInterface');
        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($route, $params) {
                return serialize($params);
            });

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $twig->expects($this->any())
            ->method('render')
            ->willReturnCallback(function ($template, $params) {
                return serialize($params);
            });

        $this->managerRegistry = $this->getMockBuilder('\Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DeleteMessageTextGenerator($this->router, $twig, $this->managerRegistry);
    }

    public function tearDown()
    {
        unset($this->extension, $this->router, $this->managerRegistry);
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
            $paymentTerm->getAccountGroups()->count(),
            $paymentTerm->getAccounts()->count()
        );
    }

    public function testGetDeleteMessageTextForDataGrid()
    {
        $paymentTermId = 1;

        $paymentTermRepository = $this->getMockBuilder(
            'OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTermRepository->expects($this->once())
            ->method('find')
            ->with($paymentTermId)
            ->willReturnCallback(function ($id) {
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
     * @param string $message
     * @param int $paymentTermId
     * @param int $accountGroupCount
     * @param int $accountCount
     */
    public function assertDeleteMessage($message, $paymentTermId, $accountGroupCount, $accountCount)
    {
        $message = unserialize($message);

        $this->assertArrayHasKey('accountGroupFilterUrl', $message);
        $this->assertArrayHasKey('accountFilterUrl', $message);

        if ($accountGroupCount) {
            $accountGroupFilterUrl = unserialize($message['accountGroupFilterUrl']);
            $this->assertDataFromDeleteMessage(
                $paymentTermId,
                $accountGroupFilterUrl,
                DeleteMessageTextGenerator::ACCOUNT_GROUP_GRID_NAME,
                'orob2b.account.accountgroup.entity_label'
            );
        } else {
            $this->assertNull($message['accountGroupFilterUrl']);
        }

        if ($accountCount) {
            $accountFilterUrl = unserialize($message['accountFilterUrl']);
            $this->assertDataFromDeleteMessage(
                $paymentTermId,
                $accountFilterUrl,
                DeleteMessageTextGenerator::ACCOUNT_GRID_NAME,
                'orob2b.account.entity_label'
            );
        } else {
            $this->assertNull($message['accountFilterUrl']);
        }
    }

    /**
     * @return array
     */
    public function getDeleteMessageTextDataProvider()
    {
        return [
            'payment with account and account group' => [
                'paymentTerm' => new PaymentTermStub(1, ['one account'], ['one accountGroup'])
            ],
            'payment only with account' => [
                'paymentTerm' => new PaymentTermStub(1, ['one account'], [])
            ],
            'payment only with account group' => [
                'paymentTerm' => new PaymentTermStub(1, [], ['one accountGroup'])
            ],
            'empty' => [
                'paymentTerm' => new PaymentTermStub(1, [], [])
            ],
        ];
    }

    /**
     * @param string $gridName
     * @param int $paymentTermId
     * @return array
     */
    private function generateUrlParameters($gridName, $paymentTermId)
    {
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
     * @param int $paymentTermId
     * @param string $accountFilterUrl
     * @param string $gridName
     * @param string $label
     */
    private function assertDataFromDeleteMessage($paymentTermId, $accountFilterUrl, $gridName, $label)
    {
        $urlParameters = unserialize($accountFilterUrl['urlPath']);

        $this->assertEquals(
            $this->generateUrlParameters(
                $gridName,
                $paymentTermId
            ),
            $urlParameters
        );

        $this->assertEquals($label, $accountFilterUrl['label']);
    }
}
