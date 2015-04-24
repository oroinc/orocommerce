<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus;

class LoadRequestStatusData extends AbstractFixture
{
    const NAME_NOT_DELETED = 'test1';
    const NAME_DELETED = 'deleted';

    /**
     * @var array
     */
    protected $requestStatuses = [
        [
            'order'   => 10,
            'name'    => self::NAME_NOT_DELETED,
            'label'   => 'Open',
            'locale'  => 'en_US',
            'deleted' => false
        ], [
            'order'   => 20,
            'name'    => 'test2',
            'label'   => 'In Progress',
            'locale'  => 'en_US',
            'deleted' => false
        ], [
            'order'   => 30,
            'name'    => 'test3',
            'label'   => 'Closed',
            'locale'  => 'en_US',
            'deleted' => false
        ], [
            'order'   => 40,
            'name'    => self::NAME_DELETED,
            'label'   => 'Deleted',
            'locale'  => 'en_US',
            'deleted' => true
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $om)
    {
        foreach ($this->requestStatuses as $requestStatusData) {
            $requestStatus = new RequestStatus();

            $requestStatus
                ->setSortOrder($requestStatusData['order'])
                ->setName($requestStatusData['name'])
                ->setLabel($requestStatusData['label'])
                ->setLocale($requestStatusData['locale'])
                ->setDeleted($requestStatusData['deleted'])
            ;

            $om->persist($requestStatus);
        }

        $om->flush();
    }
}
