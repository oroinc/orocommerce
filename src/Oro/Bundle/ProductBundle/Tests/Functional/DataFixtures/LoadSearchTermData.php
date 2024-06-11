<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadSearchTermData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const REDIRECT_TO_URI = 'search-term:redirect_to_uri';
    public const SHOW_PRODUCT_COLLECTION = 'search-term:show_product_collection';
    public const REDIRECT_TO_PRODUCT = 'search-term:redirect_to_product';

    private const SEARCH_TERM_DATA = [
        self::REDIRECT_TO_URI => [
            'phrases' => 'redirect_to_uri',
            'actionType' => 'redirect',
            'modifyActionType' => 'uri',
            'redirectUri' => 'https://example.com',
            'forEveryone' => true,
        ],
        self::SHOW_PRODUCT_COLLECTION => [
            'phrases' => 'show_product_collection',
            'actionType' => 'modify',
            'modifyActionType' => 'product_collection',
            'productCollectionSegment' => LoadProductCollectionData::SEGMENT,
            'forEveryone' => true,
        ],
        self::REDIRECT_TO_PRODUCT => [
            'phrases' => 'redirect_to_product',
            'actionType' => 'redirect',
            'redirectActionType' => 'product',
            'redirectProduct' => LoadProductData::PRODUCT_1,
            'forEveryone' => true,
        ],
    ];

    public function getDependencies(): array
    {
        return [
            LoadProductCollectionData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $propertyAccessor = $this->container->get('property_accessor');
        $everyone = $this->container->get('oro_scope.scope_manager')
            ->findOrCreate('searchTerm', ['website' => null, 'customerGroup' => null, 'customer' => null]);

        foreach (self::SEARCH_TERM_DATA as $reference => $data) {
            $searchTerm = new SearchTerm();

            foreach ($data as $key => $value) {
                if ($key === 'redirectProduct') {
                    $searchTerm->setRedirectProduct($this->getReference($data['redirectProduct']));
                    continue;
                }

                if ($key === 'productCollectionSegment') {
                    $searchTerm->setProductCollectionSegment($this->getReference($data['productCollectionSegment']));
                    continue;
                }

                if ($key === 'forEveryone') {
                    $searchTerm->setScopes(new ArrayCollection([$everyone]));
                    continue;
                }

                $propertyAccessor->setValue($searchTerm, $key, $value);
            }

            $this->setReference($reference, $searchTerm);

            $manager->persist($searchTerm);
        }

        $manager->flush();
    }
}
