<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;

class LoadEmployeesToIndex extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const REFERENCE_PERSON1 = 'employee1';
    const REFERENCE_PERSON2 = 'employee2';

    const PERSON_NAME1 = 'Employee name 1';
    const PERSON_NAME2 = 'Employee name 2';

    /** @var array */
    protected $data = [
        self::REFERENCE_PERSON1 => [
            'name' => self::PERSON_NAME1,
        ],
        self::REFERENCE_PERSON2 => [
            'name' => self::PERSON_NAME2,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $reference => $data) {
            $person = new TestEmployee();
            $person->setName($data['name']);
            $manager->persist($person);

            $this->addReference($reference, $person);
        }

        $manager->flush();
        $manager->clear();
    }
}
