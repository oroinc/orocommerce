<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\NotificationMessage;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;

class LoadNotificationMessages extends AbstractFixture
{
    const CHANNEL1 = 'channel1';
    const CHANNEL2 = 'channel2';

    const TOPIC1 = 'topic1';
    const TOPIC2 = 'topic2';

    const ENTITY_FQCN = '\stdClass';
    const ENTITY_ID = 1;

    const MESSAGE_C1_T1_1 = 'message_c1_t1_1';
    const MESSAGE_C1_T2_1 = 'message_c1_t2_1';
    const MESSAGE_C1_T2_2 = 'message_c1_t2_2';
    const MESSAGE_C1_T2_3 = 'message_c1_t2_3';
    const MESSAGE_C1_T2_4 = 'message_c1_t2_4';
    const MESSAGE_C2_T1_1 = 'message_c2_t1_1';
    const MESSAGE_C2_T1_2 = 'message_c2_t1_2';
    const MESSAGE_C2_T2_1 = 'message_c2_t2_1';
    const MESSAGE_C2_T2_2 = 'message_c2_t2_2';
    const MESSAGE_C2_T2_3 = 'message_c2_t2_3';

    protected static $data = [
        [
            'channel' => self::CHANNEL1,
            'topic' => self::TOPIC1,
            'status' => Message::STATUS_SUCCESS,
            'receiver_fqcn' => null,
            'receiver_id' => null,
            'resolved' => false,
            'reference' => self::MESSAGE_C1_T1_1
        ],
        [
            'channel' => self::CHANNEL1,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_SUCCESS,
            'receiver_fqcn' => null,
            'receiver_id' => null,
            'resolved' => false,
            'reference' => self::MESSAGE_C1_T2_1
        ],
        [
            'channel' => self::CHANNEL1,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_ERROR,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => null,
            'resolved' => false,
            'reference' => self::MESSAGE_C1_T2_2
        ],
        [
            'channel' => self::CHANNEL1,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_ERROR,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => self::ENTITY_ID,
            'resolved' => false,
            'reference' => self::MESSAGE_C1_T2_3
        ],
        [
            'channel' => self::CHANNEL1,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_WARNING,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => self::ENTITY_ID,
            'resolved' => true,
            'reference' => self::MESSAGE_C1_T2_4
        ],
        [
            'channel' => self::CHANNEL2,
            'topic' => self::TOPIC1,
            'status' => Message::STATUS_WARNING,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => self::ENTITY_ID,
            'resolved' => true,
            'reference' => self::MESSAGE_C2_T1_1
        ],
        [
            'channel' => self::CHANNEL2,
            'topic' => self::TOPIC1,
            'status' => Message::STATUS_WARNING,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => self::ENTITY_ID,
            'resolved' => false,
            'reference' => self::MESSAGE_C2_T1_2
        ],
        [
            'channel' => self::CHANNEL2,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_WARNING,
            'receiver_fqcn' => null,
            'receiver_id' => null,
            'resolved' => false,
            'reference' => self::MESSAGE_C2_T2_1
        ],
        [
            'channel' => self::CHANNEL2,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_WARNING,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => null,
            'resolved' => false,
            'reference' => self::MESSAGE_C2_T2_2
        ],
        [
            'channel' => self::CHANNEL2,
            'topic' => self::TOPIC2,
            'status' => Message::STATUS_WARNING,
            'receiver_fqcn' => self::ENTITY_FQCN,
            'receiver_id' => self::ENTITY_ID,
            'resolved' => false,
            'reference' => self::MESSAGE_C2_T2_3
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime();

        foreach (self::$data as $item) {
            $message = new NotificationMessage();

            $message
                ->setChannel($item['channel'])
                ->setTopic($item['topic'])
                ->setMessageStatus($item['status'])
                ->setMessage($item['reference'])
                ->setReceiverEntityFQCN($item['receiver_fqcn'])
                ->setReceiverEntityId($item['receiver_id']);

            if (!empty($item['resolved'])) {
                $message->setResolved($item['resolved']);
                $message->setResolvedAt($now);
            }


            $manager->persist($message);
            $this->setReference($item['reference'], $message);
        }

        $manager->flush();
    }
}
