<?php

declare(strict_types=1);

namespace Imi\Tars\Route;

class RouteResult
{
    /**
     * 路由配置项.
     *
     * @var RouteItem
     */
    public RouteItem $routeItem;

    /**
     * 参数.
     */
    public array $params = [];

    /**
     * 回调.
     *
     * @var callable
     */
    public $callable;

    public function __construct(RouteItem $routeItem, array $params = [])
    {
        $this->routeItem = $routeItem;
        $this->params = $params;
        $this->callable = $routeItem->callable;
    }
}
