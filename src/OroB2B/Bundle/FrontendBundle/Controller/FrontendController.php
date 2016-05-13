<?php

namespace OroB2B\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\LayoutBundle\Annotation\Layout;

class FrontendController extends Controller
{
    /**
     * @Route("/", name="orob2b_frontend_root")
     * @return RedirectResponse
     */
    public function indexAction()
    {
        return $this->redirectToRoute('orob2b_product_frontend_product_index');
    }

    /**
     * @Layout()
     * @Route("/exception/{code}/{text}", name="orob2b_frontend_exception", requirements={"code"="\d+"})
     * @param int $code
     * @param string $text
     * @return Response
     */
    public function exceptionAction($code, $text)
    {
        return [
            'data' => [
                'status_code' => $code,
                'status_text' => $text,
            ]
        ];
    }
}
