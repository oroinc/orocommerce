<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Async;

use Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadValidAndInvalidCheckoutSubtotals;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class RecalculateCheckoutSubtotalsProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadValidAndInvalidCheckoutSubtotals::class
            ]
        );
    }

    public function testProcess(): void
    {
        $message = self::sendMessage(RecalculateCheckoutSubtotalsTopic::getName(), []);

        self::assertNotEmpty($this->getRepository()->findBy(['valid' => false]));

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);

        self::assertProcessedMessageProcessor(
            'oro_checkout.async.recalculate_checkout_subtotals_processor',
            $message
        );

        self::assertEmpty($this->getRepository()->findBy(['valid' => false]));
    }

    private function getRepository(): CheckoutSubtotalRepository
    {
        return self::getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(CheckoutSubtotal::class);
    }
}
