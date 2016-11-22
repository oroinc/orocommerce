<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\NotificationMessage;
use Oro\Bundle\PricingBundle\Entity\Repository\NotificationMessageRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadNotificationMessages;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class NotificationMessageRepositoryTest extends WebTestCase
{
    /**
     * @var NotificationMessageRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadNotificationMessages::class
        ]);
        $this->repository = $this->client->getContainer()->get('doctrine')
            ->getManagerForClass(NotificationMessage::class)
            ->getRepository(NotificationMessage::class);
    }

    public function testRemoveMessagesFromChannelTopic()
    {
        $this->repository->removeMessages(
            LoadNotificationMessages::CHANNEL1,
            LoadNotificationMessages::TOPIC2
        );
        /** @var NotificationMessage[] $messages */
        $messages = $this->repository->findBy(['channel' => LoadNotificationMessages::CHANNEL1]);

        /** @var NotificationMessage $expectedMessage */
        $expectedMessage = $this->getReference(LoadNotificationMessages::MESSAGE_C1_T1_1);
        $this->assertCount(1, $messages);
        $this->assertEquals($expectedMessage->getId(), $messages[0]->getId());
    }

    public function testRemoveMessagesFromChannelTopicByEntityClass()
    {
        $this->repository->removeMessages(
            LoadNotificationMessages::CHANNEL1,
            LoadNotificationMessages::TOPIC2,
            LoadNotificationMessages::ENTITY_FQCN
        );
        /** @var NotificationMessage[] $messages */
        $messages = $this->repository->findBy(
            [
                'channel' => LoadNotificationMessages::CHANNEL1,
                'topic' => LoadNotificationMessages::TOPIC2
            ]
        );

        /** @var NotificationMessage $expectedMessage */
        $expectedMessage = $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_1);
        $this->assertCount(1, $messages);
        $this->assertEquals($expectedMessage->getId(), $messages[0]->getId());
    }

    public function testRemoveMessagesFromChannelTopicByEntity()
    {
        $this->repository->removeMessages(
            LoadNotificationMessages::CHANNEL1,
            LoadNotificationMessages::TOPIC2,
            LoadNotificationMessages::ENTITY_FQCN,
            LoadNotificationMessages::ENTITY_ID
        );
        /** @var NotificationMessage[] $messages */
        $messages = $this->repository->findBy(
            [
                'channel' => LoadNotificationMessages::CHANNEL1,
                'topic' => LoadNotificationMessages::TOPIC2
            ],
            [
                'id' => 'ASC'
            ]
        );

        $this->assertCount(2, $messages);
        /** @var NotificationMessage $expectedMessage1 */
        $expectedMessage1 = $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_1);
        $this->assertEquals($expectedMessage1->getId(), $messages[0]->getId());

        /** @var NotificationMessage $expectedMessage2 */
        $expectedMessage2 = $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_2);
        $this->assertEquals($expectedMessage2->getId(), $messages[1]->getId());
    }

    public function testReceiveMessagesForChannel()
    {
        $messages = $this->repository->receiveMessages(
            LoadNotificationMessages::CHANNEL1
        );

        $this->assertCount(4, $messages);
        $this->assertEquals(
            [
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T1_1)->getId(),
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_1)->getId(),
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_2)->getId(),
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_3)->getId(),
            ],
            $this->getMessageIds($messages)
        );
    }

    public function testReceiveMessagesForChannelEntityClass()
    {
        $messages = $this->repository->receiveMessages(
            LoadNotificationMessages::CHANNEL1,
            LoadNotificationMessages::ENTITY_FQCN
        );

        $this->assertCount(2, $messages);
        $this->assertEquals(
            [
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_2)->getId(),
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_3)->getId(),
            ],
            $this->getMessageIds($messages)
        );
    }

    public function testReceiveMessagesForChannelEntityClassTopic()
    {
        $messages = $this->repository->receiveMessages(
            LoadNotificationMessages::CHANNEL1,
            LoadNotificationMessages::ENTITY_FQCN,
            null,
            LoadNotificationMessages::TOPIC2
        );

        $this->assertCount(2, $messages);
        $this->assertEquals(
            [
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_2)->getId(),
                $this->getReference(LoadNotificationMessages::MESSAGE_C1_T2_3)->getId(),
            ],
            $this->getMessageIds($messages)
        );
    }

    public function testReceiveMessagesForChannelEntity()
    {
        $messages = $this->repository->receiveMessages(
            LoadNotificationMessages::CHANNEL2,
            LoadNotificationMessages::ENTITY_FQCN,
            LoadNotificationMessages::ENTITY_ID
        );

        $this->assertCount(2, $messages);
        $this->assertEquals(
            [
                $this->getReference(LoadNotificationMessages::MESSAGE_C2_T1_2)->getId(),
                $this->getReference(LoadNotificationMessages::MESSAGE_C2_T2_3)->getId(),
            ],
            $this->getMessageIds($messages)
        );
    }

    public function testReceiveMessagesForChannelEntityTopic()
    {
        $messages = $this->repository->receiveMessages(
            LoadNotificationMessages::CHANNEL2,
            LoadNotificationMessages::ENTITY_FQCN,
            LoadNotificationMessages::ENTITY_ID,
            LoadNotificationMessages::TOPIC1
        );

        $this->assertCount(1, $messages);
        $this->assertEquals(
            [
                $this->getReference(LoadNotificationMessages::MESSAGE_C2_T1_2)->getId(),
            ],
            $this->getMessageIds($messages)
        );
    }

    /**
     * @param array|NotificationMessage[] $messages
     * @return array|int
     */
    protected function getMessageIds(array $messages)
    {
        $ids = array_map(
            function (NotificationMessage $message) {
                return $message->getId();
            },
            $messages
        );
        sort($ids);

        return $ids;
    }
}
