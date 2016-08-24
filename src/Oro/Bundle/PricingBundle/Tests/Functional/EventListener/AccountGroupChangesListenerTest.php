<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;

/**
 * @dbIsolation
 */
class AccountGroupChangesListenerTest extends WebTestCase
{
    /**
     * @var TraceableMessageProducer
     */
    protected $messageProducer;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader(), true);

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
            ],
            true
        );
        $this->messageProducer = $this->getContainer()->get('oro_message_queue.message_producer');
        $this->messageProducer->clearTraces();
    }

    /**
     * @dataProvider deleteGroupDataProvider
     * @param string $deletedGroupReference
     * @param array $expectedMessages
     */
    public function testDeleteGroup($deletedGroupReference, array $expectedMessages)
    {
        /** @var AccountGroup $group */
        $group = $this->getReference($deletedGroupReference);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_account_delete_account_group', ['id' => $group->getId()])
        );
        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
        $this->checkQueueMessages($expectedMessages);
    }

    /**
     * @return array
     */
    public function deleteGroupDataProvider()
    {
        return [
            [
                'deletedGroupReference' => 'account_group.group1',
                'expectedMessages' => [
                    [
                        'topic' => Topics::REBUILD_PRICE_LISTS,
                        'message' => [
                            PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                            PriceListRelationTrigger::ACCOUNT => 'account.level_1.3',
                        ],
                        'priority' => 'oro.message_queue.client.normal_message_priority',
                    ],
                ],
            ],
            [
                'deletedGroupReference' => 'account_group.group2',
                'expectedMessages' => [
                    [
                        'topic' => Topics::REBUILD_PRICE_LISTS,
                        'message' => [
                            PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                            PriceListRelationTrigger::ACCOUNT => 'account.level_1.2',
                        ],
                        'priority' => 'oro.message_queue.client.normal_message_priority',
                    ],
                ],
            ],
            [
                'deletedGroupReference' => 'account_group.group3',
                'expectedMessages' => [],
            ],
        ];
    }

    /**
     * @param array $expectedMessages
     */
    protected function checkQueueMessages($expectedMessages)
    {
        $expectedMessages = array_map(
            function ($data) {
                $message = [];
                if (!empty($data['message'][PriceListRelationTrigger::ACCOUNT])) {
                    $message[PriceListRelationTrigger::ACCOUNT] = $this->getReference(
                        $data['message'][PriceListRelationTrigger::ACCOUNT]
                    )->getId();
                }
                if (!empty($data['message'][PriceListRelationTrigger::ACCOUNT_GROUP])) {
                    $message[PriceListRelationTrigger::ACCOUNT_GROUP] = $this->getReference(
                        $data['message'][PriceListRelationTrigger::ACCOUNT_GROUP]
                    )->getId();
                }
                if (!empty($data['message'][PriceListRelationTrigger::WEBSITE])) {
                    $message[PriceListRelationTrigger::WEBSITE] = $this->getReference(
                        $data['message'][PriceListRelationTrigger::WEBSITE]
                    )->getId();
                }
                $data['message'] = $message;
                return $data;
            },
            $expectedMessages
        );


        $actual = $this->messageProducer->getTraces();
        $this->assertEquals(
            $expectedMessages,
            $actual,
            "Expected messages in queue should equals actual",
            $delta = 0.0,
            $maxDepth = 10,
            $canonicalize = true
        );
    }
}
