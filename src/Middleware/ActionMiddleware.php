<?php

declare(strict_types=1);

namespace Imi\Tars\Middleware;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;
use Imi\Server\TcpServer\IReceiveHandler;
use Imi\Server\TcpServer\Message\IReceiveData;
use Imi\Server\TcpServer\Middleware\IMiddleware;
use Imi\Tars\Protocol\TARSProtocol;
use Imi\Tars\Servant\ServantBase;

/**
 * @Bean("TARSActionMiddleware")
 */
class ActionMiddleware implements IMiddleware
{
    /**
     * {@inheritDoc}
     */
    public function process(IReceiveData $data, IReceiveHandler $handler)
    {
        $requestContext = RequestContext::getContext();
        // 获取路由结果
        /** @var \Imi\Tars\Route\RouteResult|null $result */
        $result = $requestContext['routeResult'] ?? null;
        if (null === $result)
        {
            return $handler->handle($data);
        }
        $callable = &$result->callable;
        // 路由匹配结果是否是[控制器对象, 方法名]
        $isObject = \is_array($callable) && isset($callable[0]) && $callable[0] instanceof ServantBase;
        if ($isObject)
        {
            $callable[0]->server = $requestContext['server'] ?? null;
            $callable[0]->data = $data;
        }

        /** @var TARSProtocol $protocol */
        $protocol = App::getBean('TARSProtocol');

        // 解包数据
        $actionParams = $this->unpackParams($result, $data, $protocol);

        // 执行动作
        $actionResult = ($callable)(...$actionParams['args']);

        //打包数据
        $requestContext['tcpResult'] = $protocol->packRsp($result->routeItem->annotation->paramInfos,
            $actionParams['unpackResult'], $actionParams['args'], $actionResult);

        $actionResult = $handler->handle($data);

        if (null !== $actionResult)
        {
            $requestContext['tcpResult'] = $actionResult;
        }

        return $requestContext['tcpResult'];
    }

    private function unpackParams(\Imi\Tars\Route\RouteResult $routeResult, IReceiveData $data, TARSProtocol $protocol)
    {
        $paramInfo = $routeResult->routeItem->annotation->paramInfos;
        return [
            'args' => $protocol->convertToArgs($paramInfo, $data),
            'unpackResult' => $data
        ];
    }
}
