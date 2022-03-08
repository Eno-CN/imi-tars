<?php

declare(strict_types=1);

namespace Imi\Tars\Listener;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\RequestContext;
use Imi\Rpc\Contract\IRpcServer;
use Imi\Server\Route\TMiddleware;
use Imi\Server\ServerManager;
use Imi\Tars\Route\Annotation\Parser\TarsServantParser;
use Imi\Tars\Route\Annotation\TarsAction;
use Imi\Tars\Route\Annotation\TarsMiddleware;
use Imi\Tars\Route\Annotation\TarsRoute;
use Imi\Tars\Route\Annotation\TarsServant;
use Imi\Tars\Route\Route;
use Imi\Util\DelayServerBeanCallable;
use Imi\Worker;

/**
 * Tars 服务器路由初始化.
 */
class TarsRouteInit implements IEventListener
{
    use TMiddleware;

    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        $this->parseAnnotations();
    }

    /**
     * 处理注解路由.
     */
    private function parseAnnotations(): void
    {
        $controllerParser = TarsServantParser::getInstance();
        $context = RequestContext::getContext();
        foreach (ServerManager::getServers(IRpcServer::class) as $name => $server)
        {
            $context['server'] = $server;
            /** @var Route $route */
            $route = $server->getBean('TarsRoute');
            foreach ($controllerParser->getByServer($name, TarsServant::class) as $className => $classItem)
            {
                /** @var TarsServant $classAnnotation */
                $classAnnotation = $classItem->getAnnotation();
                if (null !== $classAnnotation->server && !\in_array($name, (array) $classAnnotation->server) || !$classAnnotation->servant)
                {
                    continue;
                }

                $classMiddlewares = []; // 类中间件
                /** @var TarsMiddleware $middleware */
                foreach (AnnotationManager::getClassAnnotations($className, TarsMiddleware::class) as $middleware)
                {
                    $classMiddlewares = array_merge($classMiddlewares, $this->getMiddlewares($middleware->middlewares, $name));
                }
                foreach (AnnotationManager::getMethodsAnnotations($className, TarsAction::class) as $methodName => $actionAnnotations)
                {
                    /** @var TarsRoute[] $routes */
                    $routes = AnnotationManager::getMethodAnnotations($className, $methodName, Route::class);
                    if (!$routes)
                    {
                        throw new \RuntimeException(sprintf('%s->%s method has no route', $className, $methodName));
                    }
                    // 方法中间件
                    $methodMiddlewares = [];
                    /** @var TarsMiddleware $middleware */
                    foreach (AnnotationManager::getMethodAnnotations($className, $methodName, TarsMiddleware::class) as $middleware)
                    {
                        $methodMiddlewares = array_merge($methodMiddlewares, $this->getMiddlewares($middleware->middlewares, $name));
                    }
                    // 最终中间件
                    $middlewares = array_values(array_unique(array_merge($classMiddlewares, $methodMiddlewares)));

                    foreach ($routes as $routeItem)
                    {
                        if (null === $routeItem->func)
                        {
                            $routeItem->func = $methodName;
                        }
                        $routeItem->servant = $classAnnotation->servant;
                        $route->addRuleAnnotation($routeItem, new DelayServerBeanCallable($server, $className, $methodName, [$server]), [
                            'middlewares' => $middlewares,
                        ]);
                    }
                }
            }
            if (0 === Worker::getWorkerId())
            {
                $route->checkDuplicateRoutes();
            }
            unset($context['server']);
        }
    }
}
