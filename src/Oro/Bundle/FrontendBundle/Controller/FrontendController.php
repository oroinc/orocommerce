<?php

namespace Oro\Bundle\FrontendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

class FrontendController extends Controller
{
    /**
     * @Layout
     * @Route("/", name="oro_frontend_root")
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/exception/{code}/{text}", name="oro_frontend_exception", requirements={"code"="\d+"})
     * @param Request $request
     * @param string $code
     * @param string $text
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function exceptionAction(Request $request, $code, $text)
    {
        // TODO: Get rid of manual layout rendering after layouts will be able to use custom status code (BAP-12796)
        $code = (int)$code;
        $theme = $request->attributes->get('_theme', 'default');

        $context = new LayoutContext();
        $context->set('theme', empty($theme) ? 'default' : $theme);
        $context->set('data', ['status_code' => $code, 'status_text' => $text]);

        $this->get('oro_layout.layout_context_holder')->setContext($context);

        /** @var LayoutBuilderInterface $layoutBuilder */
        $layoutBuilder = $this->get('oro_layout.layout_manager')->getLayoutBuilder();
        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');
        $layout = $layoutBuilder->getLayout($context);

        return new Response($layout->render(), $code);
    }
}
