<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadIndexItems extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ALIAS_TEMP = 'some_tmp_alias';
    const ALIAS_REAL = 'some_real_alias';

    /** @var array */
    protected $data = [
        'item1' => [
            'entity' => \stdClass::class,
            'alias' => self::ALIAS_REAL,
            'record_id' => 1,
            'title' => 'some title'
        ],
        [
            'entity' => \stdClass::class,
            'alias' => self::ALIAS_TEMP,
            'record_id' => 2,
            'title' => 'some title'
        ],
        [
            'entity' => \stdClass::class,
            'alias' => self::ALIAS_TEMP,
            'record_id' => 3,
            'title' => 'some title'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $helper = $this->container->get('oro_entity.doctrine_helper');
        $em = $helper->getEntityManager(Item::class);

        foreach ($this->data as $obj) {
            $item = new Item();
            $item->setAlias($obj['alias'])
                ->setEntity($obj['entity'])
                ->setRecordId($obj['record_id'])
                ->setTitle($obj['title']);
            $em->persist($item);
        }

        $em->flush();
        $em->clear();
    }
}
