<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class LoadOrders extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER1 = 'order1';
    const ORDER2 = 'order2';
    const ORDER3 = 'order3';
    const ORDER4 = 'order4';
    const ORDER5 = 'order5';
    const ORDER6 = 'order6';

    /**
     * @var array
     */
    protected $items = [
        [
            'identifier'    => 'simple_order',
            'owner'         => 'order.simple_user',
            'account'       => null,
            'accountUser'   => null,
        ],
        [
            'identifier'    => self::ORDER1,
            'owner'         => LoadOrderUsers::USER1,
            'account'       => LoadOrderUsers::ACCOUNT1,
            'accountUser'   => null,
        ],
        [
            'identifier'    => self::ORDER2,
            'owner'         => LoadOrderUsers::USER1,
            'account'       => LoadOrderUsers::ACCOUNT1,
            'accountUser'   => LoadOrderUsers::ACCOUNT1_USER1,
        ],
        [
            'identifier'    => self::ORDER3,
            'owner'         => LoadOrderUsers::USER1,
            'account'       => LoadOrderUsers::ACCOUNT1,
            'accountUser'   => LoadOrderUsers::ACCOUNT1_USER2,
        ],
        [
            'identifier'    => self::ORDER4,
            'owner'         => LoadOrderUsers::USER1,
            'account'       => LoadOrderUsers::ACCOUNT1,
            'accountUser'   => LoadOrderUsers::ACCOUNT1_USER3,
        ],
        [
            'identifier'    => self::ORDER5,
            'owner'         => LoadOrderUsers::USER1,
            'account'       => LoadOrderUsers::ACCOUNT2,
        ],
        [
            'identifier'    => self::ORDER6,
            'owner'         => LoadOrderUsers::USER1,
            'account'       => LoadOrderUsers::ACCOUNT2,
            'accountUser'   => LoadOrderUsers::ACCOUNT2_USER1,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->items as $item) {

            /* @var $owner AccountUser */
            $owner = $this->getReference($item['owner']);

            $order = new Order();
            $order
                ->setOwner($owner)
                ->setOrganization($owner->getOrganization())
            ;

            if (isset($item['account'])) {
                $order->setAccount($this->getReference($item['account']));
            }

            if (isset($item['accountUser'])) {
                $order->setAccountUser($this->getReference($item['accountUser']));
            }

            $manager->persist($order);
            $manager->flush($order);

            $order->setIdentifier($item['identifier']);

            $this->addReference($item['identifier'], $order);
        }

        $manager->flush();
    }
}
