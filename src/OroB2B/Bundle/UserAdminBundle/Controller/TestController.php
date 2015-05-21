<?php

namespace OroB2B\Bundle\UserAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class TestController extends Controller
{
    /**
     * @Route("/", name="orob2b_user_test_index")
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
        return [];
    }
}
