<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds links to consents pages in the commerce_footer_links menu.
 */
class LoadCommerceFooterLinksMenuData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const MENU = 'commerce_footer_links';

    /** @var array */
    protected static $menuUpdates = [
        [
            'key' => self::MENU.'_terms_and_conditions',
            'parent_key' => 'information',
            'uri' => '/terms_and_conditions',
            'default_title' => 'Terms and Conditions'
        ],
        [
            'key' =>  self::MENU.'_email_subscription',
            'parent_key' => 'information',
            'uri' => '/email_subscription',
            'default_title' => 'Email subscription'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $scope = $this->getScope();
        foreach (self::$menuUpdates as $menuUpdateData) {
            $menuUpdateData['scope'] = $scope;
            /** @var MenuUpdate $menuUpdate */
            $menuUpdate = $this->createMenuUpdate($menuUpdateData);
            $manager->persist($menuUpdate);
        }

        $manager->flush();
    }

    /**
     * @param array $data
     *
     * @return MenuUpdate
     */
    private function createMenuUpdate(array $data): MenuUpdate
    {
        $menuUpdate = new MenuUpdate();

        $menuUpdate->setMenu(self::MENU);
        $menuUpdate->setParentKey($data['parent_key']);
        $menuUpdate->setKey($data['key']);
        $menuUpdate->setUri($data['uri']);
        $menuUpdate->setActive(true);
        $menuUpdate->setCustom(true);
        $menuUpdate->setDefaultTitle($data['default_title']);
        $menuUpdate->setScope($data['scope']);

        return $menuUpdate;
    }

    /**
     * @return Scope
     */
    private function getScope(): Scope
    {
        $scopeType = $this->container->getParameter('oro_commerce_menu.menu_update.scope_type');
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->container->get('oro_scope.scope_manager');

        return $scopeManager->findOrCreate($scopeType, []);
    }
}
