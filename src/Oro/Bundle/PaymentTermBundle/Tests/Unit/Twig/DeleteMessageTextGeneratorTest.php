<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

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

    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentTermManager */
    private $paymentTermManager;

    /** @var DeleteMessageTextGenerator */
    private $extension;

    protected function setUp(): void
    {
        $this->paymentTermManager = $this->createMock(PaymentTermManager::class);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($route, $params, $referenceType) {
                $this->assertEquals(RouterInterface::ABSOLUTE_URL, $referenceType);

                return serialize($params);
            });

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->any())
            ->method('render')
            ->willReturnCallback(function ($template, $params) {
                return serialize($params);
            });

        $this->extension = new DeleteMessageTextGenerator(
            $router,
            $twig,
            $this->paymentTermManager
        );
    }

    /**
     * @dataProvider getDeleteMessageTextDataProvider
     */
    public function testGetDeleteMessageText(PaymentTerm $paymentTerm, bool $customer, bool $group)
    {
        $this->paymentTermManager->expects($this->exactly(2))
            ->method('hasAssignedPaymentTerm')
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

        $this->paymentTermManager->expects($this->once())
            ->method('getReference')
            ->willReturn(new PaymentTerm());
        $this->paymentTermManager->expects($this->atLeastOnce())
            ->method('hasAssignedPaymentTerm')
            ->willReturn(false);

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

        $this->paymentTermManager->expects($this->once())
            ->method('getReference')
            ->willReturn(null);

        $this->extension->getDeleteMessageTextForDataGrid(1);
    }

    public function assertDeleteMessage(
        string $message,
        int $paymentTermId,
        int $customerGroupCount,
        int $customerCount
    ): void {
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

    public function getDeleteMessageTextDataProvider(): array
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

    private function generateUrlParameters(string $gridName, int $paymentTermId): array
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

    private function assertDataFromDeleteMessage(
        int $paymentTermId,
        array $customerFilterUrl,
        string $gridName,
        string $label
    ): void {
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
