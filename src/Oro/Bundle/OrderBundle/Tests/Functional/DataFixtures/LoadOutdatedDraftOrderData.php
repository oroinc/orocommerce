<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadOutdatedDraftOrderData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string OUTDATED_DRAFT_ORDER_1 = 'outdated_draft_order_1';
    public const string OUTDATED_DRAFT_ORDER_2 = 'outdated_draft_order_2';
    public const string RECENT_DRAFT_ORDER = 'recent_draft_order';
    public const string REGULAR_ORDER = 'regular_order';

    private static array $orders = [
        self::OUTDATED_DRAFT_ORDER_1 => [
            'customer' => LoadCustomers::DEFAULT_ACCOUNT_NAME,
            'draftSessionUuid' => true,
            'updatedAt' => 'today -10 days',
            'identifier' => 'outdated-draft-1',
        ],
        self::OUTDATED_DRAFT_ORDER_2 => [
            'customer' => LoadCustomers::DEFAULT_ACCOUNT_NAME,
            'draftSessionUuid' => true,
            'updatedAt' => 'today -25 days',
            'identifier' => 'outdated-draft-2',
        ],
        self::RECENT_DRAFT_ORDER => [
            'customer' => LoadCustomers::DEFAULT_ACCOUNT_NAME,
            'draftSessionUuid' => true,
            'updatedAt' => 'today -3 days',
            'identifier' => 'recent-draft',
        ],
        self::REGULAR_ORDER => [
            'customer' => LoadCustomers::DEFAULT_ACCOUNT_NAME,
            'draftSessionUuid' => false,
            'updatedAt' => 'today -5 days',
            'identifier' => 'regular-order',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCustomers::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $organization = $manager->getRepository(Organization::class)->findOneBy([]);
        $user = $manager->getRepository(User::class)->findOneBy([]);

        foreach (self::$orders as $reference => $data) {
            /** @var Customer $customer */
            $customer = $this->getReference($data['customer']);

            $order = new Order();
            $order->setIdentifier($data['identifier']);
            $order->setCustomer($customer);
            $order->setOrganization($organization);
            $order->setOwner($user);
            $order->setCurrency('USD');

            if ($data['draftSessionUuid']) {
                $order->setDraftSessionUuid(UUIDGenerator::v4());
            }

            $manager->persist($order);
            $this->setReference($reference, $order);
        }

        $manager->flush();

        $filterManager = $this->container->get('oro_order.draft_session.manager.draft_session_orm_filter_manager');
        $filterManager->disable();

        $ordersByDate = [];
        foreach (self::$orders as $reference => $data) {
            $order = $this->getReference($reference);
            $ordersByDate[$data['updatedAt']][] = $order->getId();
        }

        $repository = $manager->getRepository(Order::class);
        foreach ($ordersByDate as $dateString => $orderIds) {
            $updatedAt = new \DateTime($dateString, new \DateTimeZone('UTC'));

            $repository->createQueryBuilder('o')
                ->update(Order::class, 'o')
                ->set('o.updatedAt', ':updatedAt')
                ->where('o.id IN (:ids)')
                ->setParameter('ids', $orderIds, Connection::PARAM_INT_ARRAY)
                ->setParameter('updatedAt', $updatedAt, Types::DATETIME_MUTABLE)
                ->getQuery()
                ->execute();
        }
    }
}
