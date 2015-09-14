<?php

namespace OroB2B\Bundle\CatalogBundle\Handler;

use Symfony\Component\HttpFoundation\Request;

class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';

    /** @var  Request|null */
    protected $request;

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @return bool|integer
     */
    public function getCategoryId()
    {
        if (!$this->request) {
            return false;
        }

        $value = $this->request->get(self::CATEGORY_ID_KEY);

        if (is_bool($value)) {
            return false;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return false;
    }
}
