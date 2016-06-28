<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\ColumnBuilder\CheckoutSource;

/**
 * Checkouts grid source definition DTO
 */
class CheckoutSourceDefinition
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $routeParams = [];

    /**
     * @var bool
     */
    private $linkable = false;

    /**
     * CheckoutSourceDefinition constructor.
     * @param string  $label
     * @param boolean $linkable
     * @param null    $route
     * @param array   $routeParams
     */
    public function __construct($label, $linkable, $route = null, $routeParams = [])
    {
        $this->label       = $label;
        $this->linkable    = $linkable;
        $this->route       = $route;
        $this->routeParams = $routeParams;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @return bool
     */
    public function isLinkable()
    {
        return $this->linkable;
    }
}
