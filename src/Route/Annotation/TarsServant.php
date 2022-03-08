<?php

declare(strict_types=1);

namespace Imi\Tars\Route\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;
use Imi\Rpc\Route\Annotation\Contract\IRpcController;

/**
 * Tars Servant注解.
 *
 * @Annotation
 * @Target("CLASS")
 * @Parser("Imi\Tars\Route\Annotation\Parser\TarsServantParser")
 *
 * @property string $servant 指定当前类对应的servant
 * @property string|string[]|null $server 指定当前servant允许哪些服务器使用。支持字符串或数组，默认为 null 则不限制。
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TarsServant extends Base implements IRpcController
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'servant';

    /**
     * @param string|string[]|null $server
     */
    public function __construct(?array $__data = null, $servant = '', $server = null)
    {
        parent::__construct(...\func_get_args());
    }
}
