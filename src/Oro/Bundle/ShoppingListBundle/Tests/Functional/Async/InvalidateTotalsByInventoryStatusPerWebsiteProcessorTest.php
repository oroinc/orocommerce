<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Async;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerWebsiteTopic;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class InvalidateTotalsByInventoryStatusPerWebsiteProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadShoppingListLineItems::class,
        ]);

        $this->recalculateTotals();
    }

    public function testProcess(): void
    {
        self::assertEmpty($this->getRepository()->findBy(['valid' => false]));

        $message = self::sendMessage(
            InvalidateTotalsByInventoryStatusPerWebsiteTopic::getName(),
            [
                'context' => [
                    'class' => Website::class,
                    'id' => $this->getWebsite()->getId(),
                ],
            ]
        );

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);

        self::assertProcessedMessageProcessor(
            'oro_shopping_list.async.invalidate_totals_by_inventory_status_per_website_processor',
            $message
        );

        self::assertNotEmpty($this->getRepository()->findBy(['valid' => false]));
    }

    public function testProcessWithEmptyMessageBody(): void
    {
        $this->setValid();

        self::assertEmpty($this->getRepository()->findBy(['valid' => false]));

        $message = self::sendMessage(
            InvalidateTotalsByInventoryStatusPerWebsiteTopic::getName(),
            []
        );

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);

        self::assertProcessedMessageProcessor(
            'oro_shopping_list.async.invalidate_totals_by_inventory_status_per_website_processor',
            $message
        );

        self::assertEmpty($this->getRepository()->findBy(['valid' => false]));
    }

    private function setValid(bool $isValid = true): void
    {
        $totals = $this->getRepository()->findAll();

        foreach ($totals as $total) {
            $total->setValid($isValid);
            $this->getEm()->persist($total);
        }

        $this->getEm()->flush();
    }

    private function getEm(): EntityManager
    {
        return self::getContainer()
            ->get('doctrine.orm.entity_manager');
    }

    private function getRepository(): ShoppingListTotalRepository
    {
        return $this->getEm()->getRepository(ShoppingListTotal::class);
    }

    private function getWebsite(): Website
    {
        return self::getContainer()
            ->get('oro_website.manager')
            ->getDefaultWebsite();
    }

    private function recalculateTotals(): void
    {
        /** @var ShoppingListTotalManager $totalManager */
        $totalManager = self::getContainer()
            ->get('oro_shopping_list.manager.shopping_list_total');

        for ($i = 1; $i <= 9; $i++) {
            $totalManager->recalculateTotals(
                $this->getReference(sprintf('shopping_list_%d', $i)),
                true
            );
        }
    }
}
