<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SaleBundle\Entity\Quote;

class LoadRequestData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const QUOTE1                    = 'sale.quote.1';

    const REQUEST_WITH_QUOTE        = 'request.with.quote';
    const REQUEST_WITHOUT_QUOTE     = 'request.without.quote';
    const REQUEST_WITHOUT_QUOTE_OLD = 'request.without.quote.old';

    const FIRST_NAME                = 'Grzegorz';
    const LAST_NAME                 = 'Brzeczyszczykiewicz';
    const EMAIL                     = 'test_request@example.com';
    const PO_NUMBER                 = 'CA1234USD';

    /**
     * @var array
     */
    protected $requests = [
        self::REQUEST_WITH_QUOTE => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST_WITH_QUOTE,
        ],
        self::REQUEST_WITHOUT_QUOTE => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST_WITHOUT_QUOTE,
        ],
        self::REQUEST_WITHOUT_QUOTE_OLD => [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'note' => self::REQUEST_WITHOUT_QUOTE_OLD,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->requests as $key => $rawRequest) {
            $days = $rawRequest['note'] === self::REQUEST_WITHOUT_QUOTE_OLD ? 5 : 1;

            $date = new \DateTime('now', new \DateTimeZone('UTC'));
            $date->modify(sprintf('-%d days', $days));

            $request = new Request();
            $request
                ->setFirstName($rawRequest['first_name'])
                ->setLastName($rawRequest['last_name'])
                ->setEmail($rawRequest['email'])
                ->setPhone($rawRequest['phone'])
                ->setCompany($rawRequest['company'])
                ->setRole($rawRequest['role'])
                ->setNote($rawRequest['note'])
                ->setCreatedAt($date);

            $manager->persist($request);

            $this->addReference($key, $request);
        }

        $quote = new Quote();
        $quote
            ->setQid(self::QUOTE1)
            ->setRequest($this->getReference(self::REQUEST_WITH_QUOTE));

        $manager->persist($quote);
        $manager->flush();

        $this->addReference(self::QUOTE1, $quote);
    }
}
