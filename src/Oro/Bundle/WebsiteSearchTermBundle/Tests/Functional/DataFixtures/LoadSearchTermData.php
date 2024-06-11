<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadSearchTermData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    private const SEARCH_TERM_DATA = [
        [
            'phrases' => 'foo',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://foo.bar',
            'forEveryone' => true,
            'partialMatch' => false,
            'reference' => 'search-term:foo:url:foo.bar',
        ],
        [
            'phrases' => 'bar',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://foo.bar',
            'forEveryone' => true,
            'partialMatch' => false,
            'reference' => 'search-term:bar:url:foo.bar',
        ],
        [
            'phrases' => 'lorem ipsum',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://lorem.ipsum',
            'forEveryone' => true,
            'partialMatch' => false,
            'reference' => 'search-term:loremipsum:url:lorem.ipsum',
        ],
        [
            'phrases' => 'noscope',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://foo.bar',
            'partialMatch' => true,
            'reference' => 'search-term:noscope:url:foo.bar',
        ],
        [
            'phrases' => 'foobar',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://foo.bar.partial',
            'forEveryone' => true,
            'partialMatch' => true,
            'reference' => 'search-term:foobar:url:foo.bar.partial',
        ],
        [
            'phrases' => 'baz',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://foo.baz',
            'forWebsite' => true,
            'partialMatch' => false,
            'reference' => 'search-term:baz:url:foo.baz',
        ],
        [
            'phrases' => 'bazfoo',
            'actionType' => 'redirect',
            'redirectActionType' => 'uri',
            'redirectUri' => 'https://baz.foo.partial',
            'forWebsite' => true,
            'partialMatch' => true,
            'reference' => 'search-term:bazfoo:url:baz.foo.partial',
        ],
    ];

    public function getDependencies()
    {
        return [
            LoadWebsiteData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $everyoneScope = $this->container->get('oro_scope.scope_manager')->findOrCreate(ScopeManager::BASE_SCOPE);
        $websiteScope = $this->container->get('oro_scope.scope_manager')
            ->findOrCreate(ScopeManager::BASE_SCOPE, ['website' => $this->getReference(LoadWebsiteData::WEBSITE1)]);

        foreach (self::SEARCH_TERM_DATA as $data) {
            $searchTerm = new SearchTerm();
            $searchTerm->setPhrases($data['phrases']);
            $searchTerm->setActionType($data['actionType']);
            $searchTerm->setRedirectActionType($data['redirectActionType'] ?? null);
            $searchTerm->setRedirectUri($data['redirectUri'] ?? null);
            if (isset($data['forEveryone'])) {
                $searchTerm->addScope($everyoneScope);
            }
            if (isset($data['forWebsite'])) {
                $searchTerm->addScope($websiteScope);
            }
            $searchTerm->setPartialMatch($data['partialMatch'] ?? false);

            $this->setReference($data['reference'], $searchTerm);

            $manager->persist($searchTerm);
        }
        $manager->flush();
    }
}
