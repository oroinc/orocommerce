<?php

namespace OroB2B\Bundle\MenuBundle\Migrations\Data\ORM;

use Knp\Menu\MenuFactory;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

use OroB2B\Bundle\MenuBundle\Entity\Manager\MenuItemManager;

class LoadMenuItemData extends AbstractFixture implements ContainerAwareInterface, VersionedFixtureInterface
{
    /**
     * @var MenuFactory
     */
    protected $factory;

    /**
     * @var MenuItemManager
     */
    protected $menuItemManager;

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->factory = $container->get('orob2b_menu.menu.factory');
        $this->menuItemManager = $container->get('orob2b_menu.entity.menu_item_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTopNavMenu($manager);
        $this->createQuickAccessMenu($manager);
        $this->createMainMenu($manager);
        $this->createFooterLinks($manager);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    protected function createTopNavMenu(ObjectManager $manager)
    {
        $item = $this->factory->createItem('top-nav');
        $item->addChild('My Account', ['uri' => '/account/user/profile']);
        $item->addChild('Order History', ['uri' => '/account/order']);
        $item->addChild('Sign Out', ['uri' => '/account/user/logout', 'extras' => [
            'condition' => 'is_logged_in()'
        ]]);
        $item->addChild('1-800-555-5555');
        $item->addChild('Live Chat', ['uri' => '/contact-us']);

        $menuItem = $this->menuItemManager->createFromItem($item);
        $manager->persist($menuItem);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function createQuickAccessMenu(ObjectManager $manager)
    {
        $item = $this->factory->createItem('quick-access');
        $item->addChild('Orders');
        $item->addChild('Quotes', ['uri' => '/account/quote']);
        $item->addChild('Quick Order Form', ['uri' => '/account/product/quick-add']);

        $menuItem = $this->menuItemManager->createFromItem($item);
        $manager->persist($menuItem);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function createMainMenu(ObjectManager $manager)
    {
        $item = $this->factory->createItem('main-menu');
        $item->addChild('Contact Us', ['uri' => '/contact-us']);
        $item->addChild('About', ['uri' => '/about']);

        $menuItem = $this->menuItemManager->createFromItem($item);
        $manager->persist($menuItem);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function createFooterLinks(ObjectManager $manager)
    {
        $item = $this->factory->createItem('footer-links');

        $itemInformation = $item->addChild('Information');
        $itemInformation->addChild('About Us', ['uri' => '/about']);
        $itemInformation->addChild('Customer Service', ['uri' => '/customer-service']);
        $itemInformation->addChild('Privacy Policy', ['uri' => '/privacy-policy']);
        $itemInformation->addChild('Site Map', ['uri' => '/sitemap']);
        $itemInformation->addChild('Search Terms', ['uri' => '/search/terms']);
        $itemInformation->addChild('Advanced Search', ['uri' => '/search/advanced']);
        $itemInformation->addChild('Orders and Returns', ['uri' => '/orders-and-returns']);
        $itemInformation->addChild('Contact Us', ['uri' => '/contact-us']);

        $itemWhy = $item->addChild('Why Buy From Us');
        $itemWhy->addChild('Shipping & Returns', ['uri' => '/shipping-and-returns']);
        $itemWhy->addChild('Secure Shopping', ['uri' => '/secure-shopping']);
        $itemWhy->addChild('International Shipping', ['uri' => '/international-shipping']);

        $itemMyAccount = $item->addChild('My Account');
        $itemMyAccount->addChild('Sign Out', ['uri' => '/account/user/logout']);
        $itemMyAccount->addChild('View Cart', ['uri' => '/cart']);
        $itemMyAccount->addChild('My Wishlist', ['uri' => '/wishlist']);
        $itemMyAccount->addChild('Track My Order', ['uri' => '/shipping/tracking']);
        $itemMyAccount->addChild('Help', ['uri' => '/help']);

        $menuItem = $this->menuItemManager->createFromItem($item);
        $manager->persist($menuItem);
    }
}
