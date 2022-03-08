<?php

declare(strict_types=1);

namespace Imi\Tars\Route\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;

/**
 * Tars 中间件注解.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Parser("Imi\Tars\Route\Annotation\Parser\TarsServantParser")
 *
 * @property string|string[]|null $middlewares
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class TarsMiddleware extends Base
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'middlewares';

    /**
     * @param string|string[]|null $middlewares
     */
    public function __construct(?array $__data = null, $middlewares = null)
    {
        parent::__construct(...\func_get_args());
    }
}
