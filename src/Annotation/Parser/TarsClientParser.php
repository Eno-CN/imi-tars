<?php

declare(strict_types=1);

namespace Imi\Tars\Annotation\Parser;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\Parser\BaseParser;
use Imi\Event\Event;
use Imi\Tars\Annotation\TarsClientImpl;
use Imi\Tars\Annotation\TarsService;
use Imi\Rpc\Client\Pool\RpcClientPool;

/**
 * Tars 客户端注入处理器.
 */
class TarsClientParser extends BaseParser
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
     * 根据ServantName获取对应的Servant客户端实现类.
     */
    public function getClientImpl(string $servantName)
    {
        if (isset($this->cache[$servantName]))
        {
            return $this->cache[$servantName];
        }
        $result = '';
        foreach (AnnotationManager::getAnnotationPoints(TarsClientImpl::class, 'class') as $option)
        {
            $class = $option->getClass();
            /** @var TarsClientImpl $TarsClientImplAnnotation */
            $TarsClientImplAnnotation = AnnotationManager::getClassAnnotations($class, TarsClientImpl::class)[0];
            if($servantName === $TarsClientImplAnnotation->servantName){
                $result = $class::getInstance();
				$result->connector = RpcClientPool::getService($servantName);
				//$TarsServiceAnnotation = AnnotationManager::getPropertyAnnotations($class, 'connector', TarsService::class)[0];
            }
        }

        return $this->cache[$servantName] = $result;
    }
}
