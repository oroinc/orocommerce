<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
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
            'from' => '/from-1',
            'to' => '/',
            'type' => Redirect::MOVED_PERMANENTLY,
            'localization' => null
        ],
        [
            'reference' => self::REDIRECT_2,
            'from' => '/from-2',
            'to' => '/to-2',
            'type' => Redirect::MOVED_PERMANENTLY,
            'localization' => 'es'
        ],
        [
            'reference' => self::REDIRECT_3,
            'from' => '/from-3',
            'to' => '/to-3',
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
            $redirect->setTo($item['to']);
            $redirect->setType($item['type']);

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
            LoadLocalizationData::class
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
