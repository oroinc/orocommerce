<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;

class LoadCheckoutWorkflowState extends AbstractFixture
{
    const CHECKOUT_STATE_1 = 'checkout.state.1';
    const CHECKOUT_STATE_2 = 'checkout.state.2';

    /**
     * @var array
     */
    protected static $states = [
        self::CHECKOUT_STATE_1 => [
            'entityId' => 7,
            'entityClass' => 'OroB2B\Bundle\SomeBundle\Entity\SomeEntity',
            'token' => 'unique_token_1',
            'stateData' => [
                'testKey' => 'testValue'
            ]
        ],
        self::CHECKOUT_STATE_2 => [
            'entityId' => 11,
            'entityClass' => 'OroB2B\Bundle\SomeBundle\Entity\SomeOtherEntity',
            'token' => 'unique_token_2',
            'stateData' => [
                'anotherTestKey' => 'anotherTestValue'
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$states as $referenceName => $state) {
            $entity = new CheckoutWorkflowState();
            $entity->setEntityId($state['entityId']);
            $entity->setEntityClass($state['entityClass']);
            $entity->setToken($state['token']);
            $entity->setStateData($state['stateData']);

            $this->addReference($referenceName, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public static function getStatesData()
    {
        return self::$states;
    }
}
