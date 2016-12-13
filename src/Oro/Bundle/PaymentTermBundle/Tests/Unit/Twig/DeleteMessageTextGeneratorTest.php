<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Manager\PaymentTermManager;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Routing\RouterInterface;

class DeleteMessageTextGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DeleteMessageTextGenerator */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PaymentTermManager */
    protected $paymentTermManager;

    protected function setUp()
    {
        $this->router = $this->getMock(RouterInterface::class);
        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(
                function ($route, $params) {
                    return serialize($params);
                }
            );

        /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject $twig */
        $twig = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $twig->expects($this->any())
            ->method('render')
            ->willReturnCallback(
                function ($template, $params) {
                    return serialize($params);
                }
            );

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTermManager = $this->getMockBuilder(PaymentTermManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DeleteMessageTextGenerator(
            $this->router,
            $twig,
            $this->paymentTermManager
        );
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @param bool $account
     * @param bool $group
     * @dataProvider getDeleteMessageTextDataProvider
     */
    public function testGetDeleteMessageText(PaymentTerm $paymentTerm, $account, $group)
    {
        $this->paymentTermManager->expects($this->exactly(2))->method('hasAssignedPaymentTerm')
            ->willReturnOnConsecutiveCalls($account, $group);
        $message = $this->extension->getDeleteMessageText($paymentTerm);
        $this->assertDeleteMessage(
            $message,
            $paymentTerm->getId(),
            $account,
            $group
        );
    }

    public function testGetDeleteMessageTextForDataGrid()
    {
        $paymentTermId = 1;

        $this->paymentTermManager->expects($this->once())->method('getReference')->willReturn(new PaymentTerm());
        $this->paymentTermManager->expects($this->atLeastOnce())->method('hasAssignedPaymentTerm')->willReturn(false);

        $message = $this->extension->getDeleteMessageTextForDataGrid($paymentTermId);
        $this->assertDeleteMessage(
            $message,
            $paymentTermId,
            0,
            0
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage PaymentTerm #1 not found
     */
    public function testGetDeleteMessageTextForDataGridWithoutPaymentTerm()
    {
        $this->paymentTermManager->expects($this->once())->method('getReference')->willReturn(null);

        $this->extension->getDeleteMessageTextForDataGrid(1);
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
                'oro.customer.accountgroup.entity_label'
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
                'oro.customer.account.entity_label'
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
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'account' => true,
                'group' => true,
            ],
            'payment only with account' => [
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'account' => true,
                'group' => false,
            ],
            'payment only with account group' => [
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'account' => false,
                'group' => true,
            ],
            'empty' => [
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'account' => false,
                'group' => false,
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
            'grid' => [
                $gridName => http_build_query(
                    [
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => [
                            $this->paymentTermManager->getAssociationName() => [
                                'value' => [$paymentTermId],
                            ],
                        ],
                    ]
                ),
            ],
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
