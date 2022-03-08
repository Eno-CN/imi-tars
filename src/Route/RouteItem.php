<?php

declare(strict_types=1);

namespace Imi\Tars\Route;

use Imi\Tars\Route\Annotation\TarsRoute;

class RouteItem
{
    /**
     * 注解.
     */
    public TarsRoute $annotation;

    /**
     * 回调.
     *
     * @var callable
     */
    public $callable;

    /**
     * 中间件列表.
     */
    public array $middlewares = [];

    /**
     * 其它配置项.
     */
    public array $options = [];

    public function __construct(TarsRoute $annotation, callable $callable, array $options = [])
    {
        $this->annotation = $annotation;
        $this->callable = $callable;
        $this->options = $options;
    }
}
