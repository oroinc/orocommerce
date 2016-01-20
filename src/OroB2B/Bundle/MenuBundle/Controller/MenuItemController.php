<?php

namespace OroB2B\Bundle\MenuBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\MenuBundle\Form\Type\MenuItemType;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

class MenuItemController extends Controller
{
    /**
     * @Route("/", name="orob2b_menu_item_roots")
     * @Template
     * @Acl(
     *      id="orob2b_menu_item_view",
     *      type="entity",
     *      class="OroB2BMenuBundle:MenuItem",
     *      permission="VIEW"
     * )
     */
    public function rootsAction()
    {
        return [];
    }

    /**
     * @Route("/view/{id}", name="orob2b_menu_item_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_menu_item_view")
     * @param MenuItem $menuItem
     * @return array|RedirectResponse
     */
    public function viewAction(MenuItem $menuItem)
    {
        if (null !== $menuItem->getParent()) {
            throw $this->createNotFoundException();
        }

        return ['entity' => $menuItem];
    }

    /**
     * @Route("/create", name="orob2b_menu_item_create_root")
     * @Acl(
     *      id="orob2b_menu_item_create_root",
     *      type="entity",
     *      class="OroB2BMenuBundle:MenuItem",
     *      permission="CREATE"
     * )
     * @Template("OroB2BMenuBundle:MenuItem:createRoot.html.twig")
     */
    public function createRootAction()
    {
        $menuItem = new MenuItem();
        $form = $this->createForm(MenuItemType::NAME, $menuItem);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $menuItem,
            $form,
            function (MenuItem $menuItem) {
                return [
                    'route' => 'orob2b_menu_item_view',
                    'parameters' => ['id' => $menuItem->getId()],
                ];
            },
            ['route' => 'orob2b_menu_item_roots'],
            $this->get('translator')->trans('orob2b.menu.controller.menuitem.root.saved.message')
        );
    }

    /**
     * @Route("/create/{id}", name="orob2b_menu_item_create")
     * @Acl(
     *      id="orob2b_menu_item_create",
     *      type="entity",
     *      class="OroB2BMenuBundle:MenuItem",
     *      permission="CREATE"
     * )
     * @Template("OroB2BMenuBundle:MenuItem:update.html.twig")
     * @param MenuItem $parent
     *
     * @return array|RedirectResponse
     */
    public function createChildAction(MenuItem $parent)
    {
        $child = new MenuItem();
        $child->setParent($parent);
        $child->setRoot($parent->getRoot());

        return $this->update($child);
    }

    /**
     * @Route("/update/{id}", name="orob2b_menu_item_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_menu_item_update",
     *      type="entity",
     *      class="OroB2BMenuBundle:MenuItem",
     *      permission="EDIT"
     * )
     * @param MenuItem $menuItem
     * @return array|RedirectResponse
     */
    public function updateAction(MenuItem $menuItem)
    {
        if (null === $menuItem->getParent()) {
            throw $this->createNotFoundException();
        }

        return $this->update($menuItem);
    }

    /**
     * @param MenuItem $menuItem
     * @return array|RedirectResponse
     */
    protected function update(MenuItem $menuItem)
    {
        $rootId = $menuItem->getRoot();
        $root = $this->getDoctrine()
            ->getManagerForClass('OroB2BMenuBundle:MenuItem')
            ->getRepository('OroB2BMenuBundle:MenuItem')
            ->find($rootId);

        $form = $this->createForm(MenuItemType::NAME, $menuItem);

        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $menuItem,
            $form,
            function (MenuItem $menuItem) {
                return [
                    'route' => 'orob2b_menu_item_update',
                    'parameters' => ['id' => $menuItem->getId()],
                ];
            },
            [
                'route' => 'orob2b_menu_item_view',
                'parameters' => ['id' => $rootId]
            ],
            $this->get('translator')->trans('orob2b.menu.controller.menuitem.saved.message'),
            null,
            function (MenuItem $entity, FormInterface $form) use ($root) {
                return [
                    'form' => $form->createView(),
                    'root' => $root
                ];
            }
        );
    }
}
