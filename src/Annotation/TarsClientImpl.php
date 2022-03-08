<?php

declare(strict_types=1);

namespace Imi\Tars\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;

/**
 * Tars客户端实现注解.
 *
 * @Annotation
 * @Target("CLASS")
 * @Parser("Imi\Bean\Parser\NullParser")
 *
 * @property string $servantName 客户端实现的Servant
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TarsClientImpl extends Base
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'servantName';

    /**
     * @param array|null $__data
     * @param null $servantName
     */
    public function __construct(?array $__data = null, $servantName = null)
    {
        parent::__construct($__data, '',$servantName);
    }
}
