<?php

declare(strict_types=1);

namespace Imi\Tars\Route\Annotation\Parser;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\Parser\BaseParser;
use Imi\Config;
use Imi\Event\Event;

/**
 * Servant注解处理器.
 */
class TarsServantParser extends BaseParser
{
    protected array $cache = [];

    /**
     * {@inheritDoc}
     */
    public function parse(\Imi\Bean\Annotation\Base $annotation, string $className, string $target, string $targetName): void
    {
        $eventName = 'IMI.TARS.ANNOTATION.PARSER:' . \get_class($annotation);
        Event::trigger($eventName, compact('annotation', 'className', 'target', 'targetName'), $this);
    }

    /**
     * 根据服务器获取对应的控制器数据.
     */
    public function getByServer(string $serverName, string $servantAnnotationClass): array
    {
        if (isset($this->cache[$serverName]))
        {
            return $this->cache[$serverName];
        }
        $namespaces = Config::get('@server.' . $serverName . '.beanScan', []);
        foreach ($namespaces as &$namespace)
        {
            if ('\\' !== substr($namespace, -1, 1))
            {
                $namespace .= '\\';
            }
        }
        unset($namespace);
        $result = [];
        foreach (AnnotationManager::getAnnotationPoints($servantAnnotationClass, 'class') as $option)
        {
            $class = $option->getClass();
            foreach ($namespaces as $namespace)
            {
                if (str_starts_with($class, $namespace))
                {
                    $result[$class] = $option;
                    continue 2;
                }
            }
        }

        return $this->cache[$serverName] = $result;
    }
}
