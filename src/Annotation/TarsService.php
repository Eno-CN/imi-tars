<?php

declare(strict_types=1);

namespace Imi\Tars\Annotation;

use Imi\Aop\Annotation\Inject;
use Imi\Bean\Annotation\Inherit;
use Imi\Bean\Annotation\Parser;
use Imi\Rpc\Client\Pool\RpcClientPool;

/**
 * Tars 服务对象注入.
 *
 * @Inherit
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 *
 * @property string|null $serviceName 服务名称
 * @property string|null $poolName    连接池名称
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TarsService extends Inject
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'serviceName';

    public function __construct(?array $__data = null, string $name = '', array $args = [], ?string $poolName = null, ?string $serviceName = null)
    {
        parent::__construct(...\func_get_args());
    }

    /**
     * 获取注入值的真实值
     *
     * @return mixed
     */
    public function getRealValue()
    {
        return RpcClientPool::getService($this->serviceName, $this->poolName);
    }
}
