<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountGroupChangesListenerTest extends WebTestCase
{
    /**
     * @param bool $api
     */
    protected function load($api = true)
    {
        $this->initClient([], $api ? $this->generateWsseAuthHeader() : $this->generateBasicAuthHeader(), true);

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
            ],
            true
        );
        $this->disableRealTimeMode();
    }

    /**
     * @dataProvider updateAccountDataProvider
     * @param $accountGroupReference
     * @param string $accountReference
     * @param array $expectedChanges
     * @internal param string $actualGroupReference
     */
    public function testUpdateAccount($accountGroupReference, $accountReference, array $expectedChanges)
    {
        $this->load(false);
        $em = $this->getContainer()->get('doctrine')->getManager();
        $previousChanges = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')->findAll();
        /** @var Account $account */
        $account = $this->getReference($accountReference);
        /** @var AccountGroup $group */
        $group = $this->getReference($accountGroupReference);
        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_update', ['id' => $account->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $form->setValues(['orob2b_account_type[group]' => $group->getId()]);
        $this->client->submit($form);
        $changes = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')->findAll();
        $expectedCount = count($expectedChanges) + count($previousChanges);
        $this->assertCount($expectedCount, $changes);
        $this->checkChanges($expectedChanges, $changes);
    }

    /**
     * @return array
     */
    public function updateAccountDataProvider()
    {
        return [
            [
                'accountGroupReference' => 'account_group.group2',
                'accountReference' => 'account.level_1.3',
                'expectedChanges' => [
                    [
                        'accountReference' => 'account.level_1.3',
                        'websiteReference' => 'US'
                    ]
                ]
            ],
            [
                'accountGroupReference' => 'account_group.group3',
                'accountReference' => 'account.level_1.3',
                'expectedChanges' => [
                    [
                        'accountReference' => 'account.level_1.3',
                        'websiteReference' => 'US'
                    ]
                ]
            ],
            [
                'accountGroupReference' => 'account_group.group3',
                'accountReference' => 'account.level_1.2',
                'expectedChanges' => [
                    [
                        'accountReference' => 'account.level_1.2',
                        'websiteReference' => 'US'
                    ]
                ]
            ],
            [
                'accountGroupReference' => 'account_group.group2',
                'accountReference' => 'account.level_1',
                'expectedChanges' => []
            ]
        ];
    }

    /**
     * @dataProvider deleteGroupDataProvider
     * @param string $deletedGroupReference
     * @param array $expectedChanges
     */
    public function testDeleteGroup($deletedGroupReference, array $expectedChanges)
    {
        $this->load();
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var AccountGroup $group */
        $group = $this->getReference($deletedGroupReference);

        $previousChanges = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')->findAll();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_account_delete_account_group', ['id' => $group->getId()])
        );
        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);
        $changes = $em->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')->findAll();
        $expectedChangesCount = count($expectedChanges) + count($previousChanges);
        $this->assertCount($expectedChangesCount, $changes);
        $this->checkChanges($expectedChanges, $changes);
    }

    /**
     * @return array
     */
    public function deleteGroupDataProvider()
    {
        return [
            [
                'deletedGroupReference' => 'account_group.group1',
                'expectedChanges' => [
                    [
                        'accountReference' => 'account.level_1.3',
                        'websiteReference' => 'US'
                    ]
                ]
            ],
            [
                'deletedGroupReference' => 'account_group.group2',
                'expectedChanges' => [
                    [
                        'accountReference' => 'account.level_1.2',
                        'websiteReference' => 'US'
                    ]
                ]
            ],
            [
                'deletedGroupReference' => 'account_group.group3',
                'expectedChanges' => []
            ]
        ];
    }

    /**
     * @param array $expectedChanges
     * @param PriceListChangeTrigger[] $actual
     */
    protected function checkChanges($expectedChanges, $actual)
    {
        foreach ($expectedChanges as $expected) {
            /** @var Account $account */
            $account = $this->getReference($expected['accountReference']);
            /** @var Website $website */
            $website = $this->getReference($expected['websiteReference']);
            $exist = false;
            foreach ($actual as $actualChanges) {
                if ($actualChanges->getAccount() && $actualChanges->getAccount()->getId() === $account->getId()
                    && $actualChanges->getWebsite()->getId() === $website->getId()
                ) {
                    $exist = true;
                    break;
                }
            }
            $this->assertTrue($exist);
        }
    }

    /**
     * Disable realtime price update mode
     */
    protected function disableRealTimeMode()
    {
        $configManager = $this->getContainer()->get('oro_config.scope.global');
        $configManager->set(
            Configuration::getConfigKeyByName(
                Configuration::PRICE_LISTS_UPDATE_MODE
            ),
            CombinedPriceListQueueConsumer::MODE_SCHEDULED
        );
        $configManager->flush();
    }
}
