<?php

namespace OroB2B\Bundle\UserAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class GroupController extends Controller
{
    /**
     * @Route("/", name="orob2b_user_admin_group_index")
     * @Template
     * @Acl(
     *      id="orob2b_user_admin_group_view",
     *      type="entity",
     *      class="OroB2BUserAdminBundle:Group",
     *      permission="VIEW"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_user_admin.group.entity.class')
        ];
    }
}
