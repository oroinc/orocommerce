<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCategoryVisibilityScopes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->container->get('oro_scope.scope_manager');

        $this->setReference(
            'category_visibility_scope',
            $scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, [])
        );
    }
}
