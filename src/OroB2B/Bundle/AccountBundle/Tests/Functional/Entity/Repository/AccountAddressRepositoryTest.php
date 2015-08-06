<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountAddressRepository;

/**
 * @dbIsolation
 */
class AccountAddressRepositoryTest extends WebTestCase
{
    /**
     * @var AccountAddressRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:AccountAddress');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses'
            ]
        );
    }

    /**
     * @dataProvider addressesDataProvider
     * @param string $accountReference
     * @param string $type
     * @param array $expectedAddressReferences
     */
    public function testGetAddressesByType($accountReference, $type, array $expectedAddressReferences)
    {
        /** @var Account $account */
        $account = $this->getReference($accountReference);

        /** @var AccountAddress[] $actual */
        $actual = $this->repository->getAddressesByType($account, $type);
        $this->assertCount(count($expectedAddressReferences), $actual);
        $addressIds = [];
        foreach ($actual as $address) {
            $addressIds[] = $address->getId();
        }
        foreach ($expectedAddressReferences as $addressReference) {
            $this->assertContains($this->getReference($addressReference)->getId(), $addressIds);
        }
    }

    /**
     * @return array
     */
    public function addressesDataProvider()
    {
        return [
            [
                'account.level_1',
                'billing',
                [
                    'account.level_1.address_1',
                    'account.level_1.address_2',
                    'account.level_1.address_3'
                ]
            ],
            [
                'account.level_1',
                'shipping',
                [
                    'account.level_1.address_1',
                    'account.level_1.address_3'
                ]
            ]
        ];
    }

    /**
     * @dataProvider defaultAddressDataProvider
     * @param string $accountReference
     * @param string $type
     * @param string $expectedAddressReference
     */
    public function testGetDefaultAddressesByType($accountReference, $type, $expectedAddressReference)
    {
        /** @var Account $account */
        $account = $this->getReference($accountReference);

        /** @var AccountAddress[] $actual */
        $actual = $this->repository->getDefaultAddressesByType($account, $type);
        $this->assertCount(1, $actual);
        $this->assertEquals($this->getReference($expectedAddressReference)->getId(), $actual[0]->getId());
    }

    /**
     * @return array
     */
    public function defaultAddressDataProvider()
    {
        return [
            [
                'account.level_1',
                'billing',
                'account.level_1.address_2'
            ],
            [
                'account.level_1',
                'shipping',
                'account.level_1.address_1'
            ]
        ];
    }
}
