<?php

declare(strict_types=1);

namespace Imi\Tars\Annotation;

use Imi\Aop\Annotation\Inject;
use Imi\Bean\Annotation\Inherit;
use Imi\Tars\Annotation\Parser\TarsClientParser;

/**
 * Tars 客户端注入.
 *
 * @Inherit
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 *
 * @property string|null $servantName
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TarsClient extends Inject
{
    protected ?string $defaultFieldName = 'servantName';

    public function __construct(?array $__data = null, string $name = '', array $args = [], ?string $servantName = null)
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
        //找到指定servantName的@TarsClientImpl注解对应的类
        return TarsClientParser::getInstance()->getClientImpl($this->servantName);
    }
}
