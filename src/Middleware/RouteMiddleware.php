<?php

declare(strict_types=1);

namespace Imi\Tars\Middleware;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\Annotation\ServerInject;
use Imi\Server\TcpServer\Error\ITcpRouteNotFoundHandler;
use Imi\Server\TcpServer\IReceiveHandler;
use Imi\Server\TcpServer\Message\IReceiveData;
use Imi\Server\TcpServer\Middleware\IMiddleware;
use Imi\Tars\Route\Route;

/**
 * @Bean(name="TARSRouteMiddleware", recursion=false)
 */
class RouteMiddleware implements IMiddleware
{
    /**
     * @ServerInject("TarsRoute")
     */
    protected Route $route;

    /**
     * @ServerInject("TarsRouteNotFoundHandler")
     */
    protected ITcpRouteNotFoundHandler $notFoundHandler;

    /**
     * {@inheritDoc}
     */
    public function process(IReceiveData $data, IReceiveHandler $handler)
    {
        // 路由解析
        $result = $this->route->parse($data->getFormatData());
        if (null === $result || !\is_callable($result->callable))
        {
            // 未匹配到路由
            return $this->notFoundHandler->handle($data, $handler);
        }
        else
        {
            RequestContext::set('routeResult', $result);
            return $handler->handle($data);
        }
    }
}
