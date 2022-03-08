<?php

declare(strict_types=1);

namespace Imi\Tars\Route\Annotation;

use Imi\Bean\Annotation\Parser;
use Imi\Rpc\Route\Annotation\RpcRoute;

/**
 * Tars 路由注解.
 *
 * @Annotation
 * @Target("METHOD")
 * @Parser("Imi\Tars\Route\Annotation\Parser\TarsServantParser")
 *
 * @property string $func    方法名
 * @property string $servant  方法所属servant
 * @property array  $paramInfos 参数信息（由param注释自动生成）
 * @property string $rpcType  RPC 协议类型；继承本类后必须赋值
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class TarsRoute extends RpcRoute
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'func';

    public function __toString()
    {
        return http_build_query($this->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function __construct(?array $__data = null, $servant = null, $func = null, array $paramInfos = [], string $rpcType = 'Tars')
    {
        parent::__construct(...\func_get_args());
    }
}
