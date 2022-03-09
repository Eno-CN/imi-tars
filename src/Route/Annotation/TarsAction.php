<?php

declare(strict_types=1);

namespace Imi\Tars\Route\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;
use Imi\Rpc\Route\Annotation\Contract\IRpcAction;

/**
 * Tars 方法注解.
 *
 * @Annotation
 * @Target("METHOD")
 * @Parser("Imi\Tars\Route\Annotation\Parser\TarsServantParser")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class TarsAction extends Base implements IRpcAction
{
}
