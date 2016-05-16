<?php

namespace OroB2B\Bundle\MenuBundle\Migrations\Data\ORM;

use Knp\Menu\MenuFactory;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

use OroB2B\Bundle\MenuBundle\Entity\Manager\MenuItemManager;

class LoadMenuItemData extends AbstractFixture implements
    ContainerAwareInterface,
    VersionedFixtureInterface,
    OrderedFixtureInterface
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
     * @var RouterInterface
     */
    protected $router;

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
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->factory = $container->get('orob2b_menu.menu.factory');
        $this->menuItemManager = $container->get('orob2b_menu.entity.menu_item_manager');
        $this->router = $container->get('router');
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
        $item->addChild('1-800-555-5555');
        $item->addChild('Live Chat', ['uri' => '/contact-us']);
        $item->addChild('<span>Fast & Free Shipping</span> for orders over $45', ['uri' => '/about'])
            ->setAttribute('class', 'topbar__controls')
            ->setLinkAttribute('class', 'cart__promo__link');

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
        $item->addChild('Quotes', ['uri' => $this->router->generate('orob2b_sale_quote_frontend_index')]);
        $item->addChild('Quick Order Form', ['uri' => $this->router->generate('orob2b_product_frontend_quick_add')]);

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
        $itemMyAccount->addChild(
            'Sign Out',
            ['uri' => $this->router->generate('orob2b_account_account_user_security_logout')]
        );
        $itemMyAccount->addChild('View Cart', ['uri' => $this->router->generate('orob2b_shopping_list_frontend_view')]);
        $itemMyAccount->addChild(
            'My Wishlist',
            ['uri' => $this->router->generate('orob2b_shopping_list_frontend_view')]
        );
        $itemMyAccount->addChild('Track My Order', ['uri' => '/shipping/tracking']);
        $itemMyAccount->addChild('Help', ['uri' => '/help']);

        $menuItem = $this->menuItemManager->createFromItem($item);
        $manager->persist($menuItem);
    }
}
