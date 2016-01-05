<?php

namespace OroB2B\Bundle\MenuBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

class MenuItemController extends Controller
{
    /**
     * @Route("/", name="orob2b_menu_item_index")
     * @Template
     * @Acl(
     *      id="orob2b_menu_item_view",
     *      type="entity",
     *      class="OroB2BMenuBundle:MenuItem",
     *      permission="VIEW"
     * )
     * @return array|RedirectResponse
     */
    public function indexAction()
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
        return ['entity' => $menuItem];
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
        return $this->update($menuItem);
    }

    /**
     * @param MenuItem $menuItem
     * @return array|RedirectResponse
     */
    protected function update(MenuItem $menuItem)
    {
        $form = $this->createFormBuilder($menuItem)
            ->add('uri')
            ->getForm();
        return $this->get('oro_form.model.update_handler')->handleUpdate(
            $menuItem,
            $form,
            function (MenuItem $menuItem) {
                return [
                    'route' => 'orob2b_menu_item_update',
                    'parameters' => ['id' => $menuItem->getId()],
                ];
            },
            ['route' => 'orob2b_menu_item_index'],
            $this->get('translator')->trans('orob2b.menu.controller.menuitem.saved.message')
        );
    }
}
