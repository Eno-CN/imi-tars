<?php

declare(strict_types=1);

namespace Imi\Tars\Route\Annotation\Parser;

use Imi\Server\DataParser\IParser;

class TarsDataParser implements IParser
{
    /**
     * {@inheritDoc}
     */
    public function encode($data): string
    {
        return $data; //数据已编码
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $data)
    {
        return \TUPAPI::decodeReqPacket($data);
    }
}