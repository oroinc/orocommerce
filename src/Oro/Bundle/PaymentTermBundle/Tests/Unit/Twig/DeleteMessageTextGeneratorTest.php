<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Manager\PaymentTermManager;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class DeleteMessageTextGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DeleteMessageTextGenerator */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouterInterface */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentTermManager */
    protected $paymentTermManager;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(
                function ($route, $params, $referenceType) {
                    $this->assertEquals(RouterInterface::ABSOLUTE_URL, $referenceType);
                    return serialize($params);
                }
            );

        /** @var Environment|\PHPUnit\Framework\MockObject\MockObject $twig */
        $twig = $this->getMockBuilder(Environment::class)
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
     * @param bool $customer
     * @param bool $group
     * @dataProvider getDeleteMessageTextDataProvider
     */
    public function testGetDeleteMessageText(PaymentTerm $paymentTerm, $customer, $group)
    {
        $this->paymentTermManager->expects($this->exactly(2))->method('hasAssignedPaymentTerm')
            ->willReturnOnConsecutiveCalls($customer, $group);
        $message = $this->extension->getDeleteMessageText($paymentTerm);
        $this->assertDeleteMessage(
            $message,
            $paymentTerm->getId(),
            $customer,
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

    public function testGetDeleteMessageTextForDataGridWithoutPaymentTerm()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PaymentTerm #1 not found');

        $this->paymentTermManager->expects($this->once())->method('getReference')->willReturn(null);

        $this->extension->getDeleteMessageTextForDataGrid(1);
    }

    /**
     * @param string $message
     * @param int $paymentTermId
     * @param int $customerGroupCount
     * @param int $customerCount
     */
    public function assertDeleteMessage($message, $paymentTermId, $customerGroupCount, $customerCount)
    {
        $message = unserialize($message);

        $this->assertArrayHasKey('customerGroupFilterUrl', $message);
        $this->assertArrayHasKey('customerFilterUrl', $message);

        if ($customerGroupCount) {
            $customerGroupFilterUrl = unserialize($message['customerGroupFilterUrl']);
            $this->assertDataFromDeleteMessage(
                $paymentTermId,
                $customerGroupFilterUrl,
                DeleteMessageTextGenerator::ACCOUNT_GROUP_GRID_NAME,
                'oro.customer.customergroup.entity_label'
            );
        } else {
            $this->assertNull($message['customerGroupFilterUrl']);
        }

        if ($customerCount) {
            $customerFilterUrl = unserialize($message['customerFilterUrl']);
            $this->assertDataFromDeleteMessage(
                $paymentTermId,
                $customerFilterUrl,
                DeleteMessageTextGenerator::ACCOUNT_GRID_NAME,
                'oro.customer.customer.entity_label'
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
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'customer' => true,
                'group' => true,
            ],
            'payment only with customer' => [
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'customer' => true,
                'group' => false,
            ],
            'payment only with customer group' => [
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'customer' => false,
                'group' => true,
            ],
            'empty' => [
                'paymentTerm' => $this->getEntity(PaymentTerm::class, ['id' => 1]),
                'customer' => false,
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
     * @param string $customerFilterUrl
     * @param string $gridName
     * @param string $label
     */
    private function assertDataFromDeleteMessage($paymentTermId, $customerFilterUrl, $gridName, $label)
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
