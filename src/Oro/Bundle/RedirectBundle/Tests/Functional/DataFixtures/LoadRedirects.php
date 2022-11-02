<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadRedirects extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const REDIRECT_1 = 'redirect1';
    const REDIRECT_2 = 'redirect2';
    const REDIRECT_3 = 'redirect3';

    /**
     * @var array
     */
    private $redirects = [
        [
            'reference' => self::REDIRECT_1,
            'slug' => LoadSlugsData::SLUG_URL_ANONYMOUS,
            'from' => '/from-1',
            'from_prototype' => 'from-1',
            'to' => '/',
            'to_prototype' => '',
            'type' => Redirect::MOVED_PERMANENTLY,
            'localization' => null
        ],
        [
            'reference' => self::REDIRECT_2,
            'slug' => LoadSlugsData::SLUG_URL_PAGE,
            'from' => '/from-2',
            'from_prototype' => 'from-2',
            'to' => '/to-2',
            'to_prototype' => 'to-2',
            'type' => Redirect::MOVED_PERMANENTLY,
            'localization' => 'es'
        ],
        [
            'reference' => self::REDIRECT_3,
            'slug' => null,
            'from' => '/from-3',
            'from_prototype' => 'from-3',
            'to' => '/to-3',
            'to_prototype' => 'to-3',
            'type' => Redirect::MOVED_TEMPORARY,
            'localization' => 'en_US'
        ]
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->container->get('oro_scope.scope_manager');
        foreach ($this->redirects as $item) {
            $redirect = new Redirect();
            $redirect->setFrom($item['from']);
            $redirect->setFromPrototype($item['from_prototype']);
            $redirect->setTo($item['to']);
            $redirect->setToPrototype($item['to_prototype']);
            $redirect->setType($item['type']);

            if (!empty($item['slug'])) {
                /** @var Slug $slug */
                $slug = $this->getReference($item['slug']);
                $redirect->setSlug($slug);
            }

            if ($item['localization']) {
                /** @var Website $website */
                $website = $this->getReference($item['localization']);
                $scope = $scopeManager->findOrCreate('web_content', ['localization' => $website]);
                $redirect->addScope($scope);
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
            LoadLocalizationData::class,
            LoadSlugsData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
