<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountGroupChangesListenerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader(), true);

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
            ],
            true
        );
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
        $em = $this->getContainer()->get('doctrine')->getManager();
        $actualChanges = $em->getRepository('OroB2BPricingBundle:ChangedPriceListCollection')->findAll();
        $this->assertCount(0, $actualChanges);
        /** @var AccountGroup $group */
        $group = $this->getReference($accountGroupReference);
        /** @var Account $account */
        $account = $this->getReference($accountReference);
        $account->setGroup($group);
        $em->flush();
        $changes = $em->getRepository('OroB2BPricingBundle:ChangedPriceListCollection')->findAll();
        $this->assertEquals(count($expectedChanges), count($changes));
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
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var AccountGroup $group */
        $group = $this->getReference($deletedGroupReference);
        $actualChanges = $em->getRepository('OroB2BPricingBundle:ChangedPriceListCollection')->findAll();
        $this->assertCount(0, $actualChanges);
        $em->remove($group);
        $em->flush();
        $changes = $em->getRepository('OroB2BPricingBundle:ChangedPriceListCollection')->findAll();
        $this->assertEquals(count($expectedChanges), count($changes));
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
     * @param ChangedPriceListCollection[] $actual
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
                $this->assertNull($actualChanges->getAccountGroup());
                if ($actualChanges->getAccount()->getId() == $account->getId()
                    && $actualChanges->getWebsite()->getId() == $website->getId()
                ) {
                    $exist = true;
                    break;
                }
            }
            $this->assertTrue($exist);
        }
    }
}
