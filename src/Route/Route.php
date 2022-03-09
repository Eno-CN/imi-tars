<?php

declare(strict_types=1);

namespace Imi\Tars\Route;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Bean\BeanFactory;
use Imi\Log\Log;
use Imi\Tars\Protocol\TARSProtocol;
use Imi\Tars\Route\Annotation\TarsRoute;
use Imi\Util\DelayServerBeanCallable;
use Imi\Util\ObjectArrayHelper;

/**
 * @Bean("TarsRoute")
 */
class Route implements IRoute
{
    /**
     * 路由规则.
     *
     * @var RouteItem[]
     */
    protected array $rules = [];

    /**
     * {@inheritDoc}
     */
    public function parse($data): ? RouteResult
    {
        foreach ($this->rules as $item)
        {
            if ($this->checkCondition($data, $item->annotation))
            {
                return new RouteResult($item);
            }
        }

        return null;
    }

    /**
     * 增加路由规则，直接使用注解方式.
     * @param TarsRoute $annotation
     * @param $callable
     * @param array $options
     * @throws \ReflectionException
     */
    public function addRuleAnnotation(TarsRoute $annotation, $callable, array $options = []): void
    {
        $class = $callable->getBeanName();
        $method = $callable->getMethodName();
        $ref = new \ReflectionMethod($class, $method);
        $docblock = $ref->getDocComment();
        /** @var TARSProtocol $TARSProtocol */
        $TARSProtocol = App::getBean('TARSProtocol');
        $annotation->paramInfos = $TARSProtocol->parseParamsAnnotation($docblock); //解析参数信息到注解中缓存

        $routeItem = new RouteItem($annotation, $callable, $options);
        if (isset($options['middlewares']))
        {
            $routeItem->middlewares = $options['middlewares'];
        }
        $this->rules[spl_object_id($annotation)] = $routeItem;
    }

    /**
     * 检查路由是否匹配.
     *
     * @param array|object $data
     */
    private function checkCondition($data, TarsRoute $annotation): bool
    {
        if (!$annotation->servant || !$annotation->func)
        {
            return false;
        }

        if(($data['sServantName'] !== $annotation->servant) || ($data['sFuncName'] !== $annotation->func)){
            return false;
        }

        return true;
    }

    /**
     * 检查重复路由.
     */
    public function checkDuplicateRoutes(): void
    {
        $first = true;
        $map = [];
        foreach ($this->rules as $routeItem)
        {
            $string = (string) $routeItem->annotation;
            if (isset($map[$string]))
            {
                if ($first)
                {
                    $first = false;
                    $this->logDuplicated($map[$string]);
                }
                $this->logDuplicated($routeItem);
            }
            else
            {
                $map[$string] = $routeItem;
            }
        }
    }

    private function logDuplicated(RouteItem $routeItem): void
    {
        $callable = $routeItem->callable;
        $route = 'servant=' . $routeItem->annotation->servant . ' | func=' . $routeItem->annotation->func;
        if ($callable instanceof DelayServerBeanCallable)
        {
            $logString = sprintf('Tars Route %s duplicated (%s::%s)', $route, $callable->getBeanName(), $callable->getMethodName());
        }
        elseif (\is_array($callable))
        {
            $class = BeanFactory::getObjectClass($callable[0]);
            $method = $callable[1];
            $logString = sprintf('Tars Route "%s" duplicated (%s::%s)', $route, $class, $method);
        }
        else
        {
            $logString = sprintf('Tars Route "%s" duplicated', $route);
        }
        Log::warning($logString);
    }
}
