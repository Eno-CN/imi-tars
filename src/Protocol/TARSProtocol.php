<?php
namespace Imi\Tars\Protocol;

use Imi\Bean\Annotation\Bean;
use Imi\Tars\Error\Code;

/**
 * @Bean(name="TARSProtocol", env="swoole")
 */
class TARSProtocol
{
    //tars统一使用version 5，如果你使用257注意修改
    const JSON_VERSION = 5;

    public function packBuffer($type, $argv, $tag, $name, $iVersion = 3)
    {
        $packMethods = [
            'bool' => '\TUPAPI::putBool',
            'byte' => '\TUPAPI::putChar',
            'char' => '\TUPAPI::putChar',
            'unsigned byte' => '\TUPAPI::putUInt8',
            'unsigned char' => '\TUPAPI::putUInt8',
            'short' => '\TUPAPI::putShort',
            'unsigned short' => '\TUPAPI::putUInt16',
            'int' => '\TUPAPI::putInt32',
            'unsigned int' => '\TUPAPI::putUInt32',
            'long' => '\TUPAPI::putInt64',
            'float' => '\TUPAPI::putFloat',
            'double' => '\TUPAPI::putDouble',
            'string' => '\TUPAPI::putString',
            'enum' => '\TUPAPI::putShort',
            'map' => '\TUPAPI::putMap',
            'vector' => '\TUPAPI::putVector',
            'struct' => '\TUPAPI::putStruct',
        ];

        $packMethod = $packMethods[$type];
        if ($iVersion === 3) {
            $buf = $packMethod($name, $argv, $iVersion);
        } else if ($iVersion === 1) {// jce类型是用tag进行区分的
            $buf = $packMethod($tag, $argv, $iVersion);
        } else if ($iVersion === self::JSON_VERSION) {
            if ($type == 'struct') {
                $tmp = get_object_vars($argv);
                unset($tmp['__fields']);
                unset($tmp['__typeName']);
                $buf = $tmp;
            } else if ($type == 'map') {
                //没有找到很好的办法从现场的map和vector中取出array，先压成buf再解出来
                $tmp = \TUPAPI::putMap('1', $argv, 1);
                $buf = \TUPAPI::getMap('1', $argv, $tmp, true, 1);
            } else if ($type == 'vector') {
                $tmp = \TUPAPI::putVector('1', $argv, 1);
                $buf = \TUPAPI::getVector('1', $argv, $tmp, true, 1);
            } else {
                $buf = $argv;
            }
        } else {
            throw new \Exception(Code::getMsg(Code::TARSSERVERENCODEERR), Code::TARSSERVERENCODEERR);
        }

        return $buf;
    }

    /**
     * @param $unpackResult
     * @param $code
     * @param $msg
     *
     * @return mixed
     */
    public function packErrRsp($unpackResult, $code, $msg)
    {
        $iVersion = $unpackResult['iVersion'];
        $cPacketType = 0;
        $iMessageType = 0;
        $iRequestId = $unpackResult['iRequestId']; // 使用协程的id
        $statuses = [];
        $encodeBufs = [];

        $msg = empty($msg) ? Code::getMsg($code ?? Code::TARSSERVERUNKNOWNERR) : $msg;

        if ($iVersion === 1) {
            $rspBuf = \TUPAPI::encodeRspPacket($iVersion, $cPacketType,
                $iMessageType, $iRequestId, $code, $msg, $encodeBufs, $statuses);
        } else if ($iVersion === self::JSON_VERSION) {
            $rspBuf = self::TupEncodeJson($iRequestId, $cPacketType, $iMessageType, null, $code, $msg, [], $statuses);
        } else {
            $servantName = $unpackResult['sServantName'];
            $funcName = $unpackResult['sFuncName'];
            $context = [];
            $iTimeout = 0;
            $statuses['STATUS_RESULT_CODE'] = $code;
            $rspBuf = \TUPAPI::encode($iVersion, $iRequestId, $servantName, $funcName, $cPacketType,
                $iMessageType, $iTimeout, $context, $statuses, $encodeBufs);
        }

        return $rspBuf;
    }

    public function packRsp($paramInfo, $unpackResult, $args, $returnVal)
    {
        try {
            $iVersion = $unpackResult['iVersion'];
            // 获取返回值之后,需要按照正确的格式进行打包,这里应该进行抽象(之后再说)
            $encodeBufs = [];
            $iRequestId = $unpackResult['iRequestId']; // 使用协程的id
            if ($iVersion === 1) {
                $return = $paramInfo['return'];
                if ($return['type'] !== 'void') {
                    $returnBuf = $this->packBuffer($return['type'], $returnVal, $return['tag'], '', $iVersion);
                    $encodeBufs[] = $returnBuf;
                }

                // 输出参数开始打包
                $outStartIndex = count($paramInfo['inParams']);

                $outParams = $paramInfo['outParams'];
                // 开始遍历输出参数,每一个都应该是完成赋值的内容
                for ($i = $outStartIndex; $i < count($args); ++$i) {
                    $j = $i - $outStartIndex;
                    $argv = $args[$i];
                    $buf = $this->packBuffer($outParams[$j]['type'], $argv, $outParams[$j]['tag'],
                        $outParams[$j]['name'], $iVersion);
                    $encodeBufs[] = $buf;
                }

                // 完成所有的打包之后,开始编码
                $cPacketType = 0;
                $iMessageType = 0;

                $statuses = [];

                $rspBuf = \TUPAPI::encodeRspPacket($iVersion, $cPacketType,
                    $iMessageType, $iRequestId, Code::TARSSERVERSUCCESS, 'success', $encodeBufs, $statuses);
            } else {
                $return = $paramInfo['return'];
                if ($return['type'] !== 'void') {
                    $returnBuf = $this->packBuffer($return['type'], $returnVal, $return['tag'], '', $iVersion);

                    $encodeBufs[''] = $returnBuf;
                }
                // 输出参数开始打包
                $outStartIndex = count($paramInfo['inParams']);

                $outParams = $paramInfo['outParams'];
                // 开始遍历输出参数,每一个都应该是完成赋值的内容
                for ($i = $outStartIndex; $i < count($args); ++$i) {
                    $j = $i - $outStartIndex;
                    $argv = $args[$i];
                    $buf = $this->packBuffer($outParams[$j]['type'], $argv, $outParams[$j]['tag'],
                        $outParams[$j]['name'], $iVersion);
                    $encodeBufs[$outParams[$j]['name']] = $buf;
                }

                // 完成所有的打包之后,开始编码
                $cPacketType = 0;
                $iMessageType = 0;
                $statuses = [];

                $servantName = $unpackResult['sServantName'];
                $funcName = $unpackResult['sFuncName'];
                $context = [];
                $iTimeout = 0;
                $statuses['STATUS_RESULT_CODE'] = Code::TARSSERVERSUCCESS;

                if ($iVersion == self::JSON_VERSION) {
                    $rspBuf = self::TupEncodeJson($iRequestId, $cPacketType, $iMessageType, $encodeBufs, 0, '', $context, $statuses);
                } else {
                    $rspBuf = \TUPAPI::encode($iVersion, $iRequestId, $servantName, $funcName, $cPacketType,
                                              $iMessageType, $iTimeout, $context, $statuses, $encodeBufs);
                }
            }

            return $rspBuf;
        } catch (\Exception $e) {
            throw new \Exception(Code::getMsg(Code::TARSSERVERENCODEERR),Code::TARSSERVERENCODEERR);
        }
    }

    public function parseParamsAnnotation($docblock)
    {
        $docblock = trim($docblock, '/** ');
        $lines = explode('*', $docblock);
        $validLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $validLines[] = $line;
            }
        }

        $index = 0;
        $inParams = [];
        $outParams = [];
        $returnParam = [];

        foreach ($validLines as $validLine)
        {
            $parts = array_filter(explode(' ', $validLine));
            if (strstr($validLine, '@param'))
            {
                ++$index;
                if (strstr($validLine, '=out='))
                {
                    $outParams[] = [
                        'type' => $parts[1],
                        'proto' => $parts[3],
                        'name' => trim($parts[2], '$'),
                        'tag' => $index,
                    ];
                }
                else
                {
                    $inParams[] = [
                        'type' => $parts[1],
                        'proto' => $parts[3] ?? '',
                        'name' => trim($parts[2], '$'),
                        'tag' => $index,
                    ];
                }
            }
            else if(strstr($validLine, '@return'))
            {
                if (count($parts) > 2)
                {
                    $returnParam = [
                        'type' => $parts[1],
                        'proto' => $parts[2],
                        'tag' => 0,
                    ];
                }
                else if (count($parts) === 2)
                {
                    $returnParam = [
                        'type' => $parts[1],
                        'tag' => 0,
                    ];
                }
            }
        }

        return [
            'inParams' => $inParams,
            'outParams' => $outParams,
            'return' => $returnParam,
        ];
    }

    // 完成了对入包的decode之后,获取到了sBuffer
    public function convertToArgs($paramInfo, $unpackResult)
    {
        try {
            $sBuffer = $unpackResult['sBuffer'];
            $iVersion = $unpackResult['iVersion'];

            $unpackMethods = [
                'bool' => '\TUPAPI::getBool',
                'byte' => '\TUPAPI::getChar',
                'char' => '\TUPAPI::getChar',
                'unsigned byte' => '\TUPAPI::getUInt8',
                'unsigned char' => '\TUPAPI::getUInt8',
                'short' => '\TUPAPI::getShort',
                'unsigned short' => '\TUPAPI::getUInt16',
                'int' => '\TUPAPI::getInt32',
                'unsigned int' => '\TUPAPI::getUInt32',
                'long' => '\TUPAPI::getInt64',
                'float' => '\TUPAPI::getFloat',
                'double' => '\TUPAPI::getDouble',
                'string' => '\TUPAPI::getString',
                'enum' => '\TUPAPI::getShort',
                'map' => '\TUPAPI::getMap',
                'vector' => '\TUPAPI::getVector',
                'struct' => '\TUPAPI::getStruct',
            ];

            $inParams = $paramInfo['inParams'];
            $args = [];

            if ($iVersion == self::JSON_VERSION) {
                $sBuffer = json_decode($sBuffer, true);
            }

            foreach ($inParams as $inParam) {
                $type = $inParam['type'];
                $unpackMethod = $unpackMethods[$type];
                if ($iVersion === 3) {
                    // 需要判断是否是简单类型,还是vector或map或struct
                    if ($type === 'map' || $type === 'vector') {
                        // 对于复杂的类型,需要进行实例化
                        $proto = $this->createInstance($inParam['proto']);
                        $value = $unpackMethod($inParam['name'], $proto, $sBuffer, false, $iVersion);
                    } elseif ($type === 'struct') {
                        // 对于复杂的类型,需要进行实例化
                        $proto = new $inParam['proto']();
                        $value = $unpackMethod($inParam['name'], $proto, $sBuffer, false, $iVersion);
                        $this->fromArray($value, $proto);
                        $value = $proto;
                    } // 基本类型
                    else {
                        $value = $unpackMethod($inParam['name'], $sBuffer, false, $iVersion);
                    }
                } else if ($iVersion === 1) {// jce类型是用tag进行区分的
                    // 需要判断是否是简单类型,还是vector或map或struct
                    if ($type === 'map' || $type === 'vector') {
                        // 对于复杂的类型,需要进行实例化
                        $proto = $this->createInstance($inParam['proto']);
                        $value = $unpackMethod($inParam['tag'], $proto, $sBuffer, false, $iVersion);
                    } elseif ($type === 'struct') {
                        // 对于复杂的类型,需要进行实例化
                        // 结构体还需要再转换回对象
                        $proto = new $inParam['proto']();
                        $value = $unpackMethod($inParam['tag'], $proto, $sBuffer, false, $iVersion);
                        $this->fromArray($value, $proto);
                        $value = $proto;
                    } // 基本类型
                    else {
                        $value = $unpackMethod($inParam['tag'], $sBuffer, false, $iVersion);
                    }
                } else if ($iVersion === self::JSON_VERSION) {
                    if ($type === 'map' || $type === 'vector') {
                        $value = $sBuffer[$inParam['name']];
                    } elseif ($type === 'struct') {
                        // 对于复杂的类型,需要进行实例化
                        $proto = new $inParam['proto']();
                        $value = $sBuffer[$inParam['name']];
                        $this->fromArray($value, $proto);
                        $value = $proto;
                    } // 基本类型
                    else {
                        $value = $sBuffer[$inParam['name']];
                    }
                } else {
                    throw new \Exception(Code::getMsg(Code::TARSSERVERDECODEERR),Code::TARSSERVERDECODEERR);
                }

                $args[] = $value;
            }

            $outParams = $paramInfo['outParams'];

            // 对于输出参数而言,所需要的仅仅是对应的实例化而已
            $index = 0;
            foreach ($outParams as $outParam) {
                ++$index;
                $type = $outParam['type'];

                $protoName = 'proto' . $index;

                // 如果是结构体
                if ($type === 'map' || $type === 'vector') {
                    $$protoName = $this->createInstance($outParam['proto']);
                    $args[] = $$protoName;
                } elseif ($type === 'struct') {
                    $$protoName = new $outParam['proto']();
                    $args[] = $$protoName;
                } else {
                    $protoName = null;
                    $args[] = $protoName;
                }
            }

            return $args;
        } catch (\Exception $e) {
            throw new \Exception(Code::getMsg(Code::TARSSERVERDECODEERR),Code::TARSSERVERDECODEERR);
        }
    }

    private function createInstance($proto)
    {
        if ($this->isBasicType($proto)) {
            return $this->convertBasicType($proto);
        } elseif (!strpos($proto, '(')) {
            $structInst = new $proto();

            return $structInst;
        } else {
            $pos = strpos($proto, '(');
            $className = substr($proto, 0, $pos);
            if ($className == '\TARS_Vector') {
                $next = trim(substr($proto, $pos, strlen($proto) - $pos), '()');
                $args[] = $this->createInstance($next);
            } elseif ($className == '\TARS_Map') {
                $next = trim(substr($proto, $pos, strlen($proto) - $pos), '()');
                $pos = strpos($next, ',');
                $left = substr($next, 0, $pos);
                $right = trim(substr($next, $pos, strlen($next) - $pos), ',');

                $args[] = $this->createInstance($left);
                $args[] = $this->createInstance($right);
            } elseif ($this->isBasicType($className)) {
                $next = trim(substr($proto, $pos, strlen($proto) - $pos), '()');
                $basicInst = $this->createInstance($next);
                $args[] = $basicInst;
            } else {
                $structInst = new $className();
                $args[] = $structInst;
            }
            $ins = new $className(...$args);
        }

        return $ins;
    }

    private function isBasicType($type)
    {
        $basicTypes = [
            '\TARS::BOOL',
            '\TARS::CHAR',
            '\TARS::CHAR',
            '\TARS::UINT8',
            '\TARS::UINT8',
            '\TARS::SHORT',
            '\TARS::UINT16',
            '\TARS::INT32',
            '\TARS::UINT32',
            '\TARS::INT64',
            '\TARS::FLOAT',
            '\TARS::DOUBLE',
            '\TARS::STRING',
            '\TARS::INT32',
        ];

        return in_array($type, $basicTypes);
    }

    private function convertBasicType($type)
    {
        $basicTypes = [
            '\TARS::BOOL' => 1,
            '\TARS::CHAR' => 2,
            '\TARS::UINT8' => 3,
            '\TARS::SHORT' => 4,
            '\TARS::UINT16' => 5,
            '\TARS::FLOAT' => 6,
            '\TARS::DOUBLE' => 7,
            '\TARS::INT32' => 8,
            '\TARS::UINT32' => 9,
            '\TARS::INT64' => 10,
            '\TARS::STRING' => 11,
        ];

        return $basicTypes[$type];
    }

    // 将数组转换成对象
    private function fromArray($data, &$structObj)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (method_exists($structObj, 'set' . ucfirst($key))) {
                    call_user_func_array([$this, 'set' . ucfirst($key)], [$value]);
                } elseif ($structObj->$key instanceof \TARS_Struct) {
                    $this->fromArray($value, $structObj->$key);
                } else {
                    $structObj->$key = $value;
                }
            }
        }
    }

    protected function TupEncodeJson($iRequestId, $cPacketType, $iMessageType, $encodeBufs, $resultCode = 0,
        $resultDesc = '', $context = [], $statuses = [])
    {
        $rspBuf = '';
        $rspBuf .= \TUPAPI::putUInt16('1', 1, 1);
        $rspBuf .= \TUPAPI::putChar('2', $cPacketType, 1);
        $rspBuf .= \TUPAPI::putInt32('3', $iRequestId, 1);
        $rspBuf .= \TUPAPI::putInt32('4', $iMessageType, 1);
        $rspBuf .= \TUPAPI::putInt32('5', $resultCode, 1);

        $vector = new \TARS_Vector(\TARS::CHAR);
        if ($encodeBufs !== null) {
            $tmp = json_encode($encodeBufs);
            for ($i = 0; $i < strlen($tmp); $i++) {
                $vector->pushBack($tmp[$i]);
            }
        }
        $rspBuf .= \TUPAPI::putVector('6', $vector, 1);

        $map = new \TARS_MAP(\TARS::STRING, \TARS::STRING);
        foreach ($statuses as $k => $v) {
            $map->pushBack([$k => $v]);
        }
        $rspBuf .= \TUPAPI::putMap('7', $map, 1);

        $rspBuf .= \TUPAPI::putString('8', $resultDesc, 1);

        $map = new \TARS_MAP(\TARS::STRING, \TARS::STRING);
        foreach ($context as $k => $v) {
            $map->pushBack([$k => $v]);
        }
        $rspBuf .= \TUPAPI::putMap('9', $map, 1);

        $len = strlen($rspBuf) + 4;
        $rspBuf = pack('N', $len) . $rspBuf;
        return $rspBuf;
    }
}
