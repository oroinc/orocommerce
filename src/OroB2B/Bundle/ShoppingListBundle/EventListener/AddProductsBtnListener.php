<?php
namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;

class AddProductsBtnListener
{
    /** @var  Request */
    protected $request;

    public function onFrontendProductGridBtns()
    {
        var_dump(func_get_args()); die();
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
}