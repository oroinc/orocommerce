<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadRedirects extends AbstractFixture implements DependentFixtureInterface
{
    const REDIRECT_1 = 'redirect1';
    const REDIRECT_2 = 'redirect2';
    const REDIRECT_3 = 'redirect3';
    
    /**
     * @var array
     */
    protected $redirects = [
        [
            'reference' => self::REDIRECT_1,
            'from' => '/from-1',
            'to' => '/to-1',
            'type' => Redirect::MOVED_PERMANENTLY,
            'website' => null
        ],
        [
            'reference' => self::REDIRECT_2,
            'from' => '/from-2',
            'to' => '/to-2',
            'type' => Redirect::MOVED_PERMANENTLY,
            'website' => LoadWebsiteData::WEBSITE1
        ],
        [
            'reference' => self::REDIRECT_3,
            'from' => '/from-3',
            'to' => '/to-3',
            'type' => Redirect::MOVED_TEMPORARY,
            'website' => LoadWebsiteData::WEBSITE2
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {

        foreach ($this->redirects as $item) {
            $redirect = new Redirect();
            $redirect->setFrom($item['from']);
            $redirect->setTo($item['to']);
            $redirect->setType($item['type']);
            if ($item['website']) {
                /** @var Website $website */
                $website = $this->getReference($item['website']);
                $redirect->setWebsite($website);
            }
            
            $manager->persist($redirect);
            
            $this->addReference($item['reference'], $redirect);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsiteData::class
        ];
    }
}
