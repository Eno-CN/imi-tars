<?php

namespace Imi\Tars;

class Translator
{
    public static function generate()
    {
        $tarsProto = self::getTarsProto();
        $template = "module {$tarsProto['appName']}_{$tarsProto['serverName']}" . PHP_EOL;
        $template .= '{' . PHP_EOL;
        foreach ($tarsProto['objNames'] as $objName) {
            $template .= PHP_EOL;
            $template .= "    interface $objName" . PHP_EOL;
            $template .= '    {' . PHP_EOL . PHP_EOL;
            $template .= '        string sayHelloWorld(string name);' . PHP_EOL . PHP_EOL;
            $template .= '    };' . PHP_EOL;
        }
        $template .= PHP_EOL . '};' . PHP_EOL;
        file_put_contents(self::getTarsPath() . DIRECTORY_SEPARATOR . "{$tarsProto['appName']}.{$tarsProto['serverName']}.tars", $template);
        self::log('Generate `' . "{$tarsProto['appName']}.{$tarsProto['serverName']}.tars" . '` successfully !');
    }

    public static function buildServer()
    {
        $interfacePath = self::getServerPath() . DIRECTORY_SEPARATOR . 'Interface';
        if (!is_dir($interfacePath)){
            exec('mkdir '. $interfacePath);
        }
        $tarsFiles = self::getTarsFiles(true);
        // TODO 解析tars文件 生成interface

    }

    public static function buildClient()
    {
        $servantPath = self::getServerPath() . DIRECTORY_SEPARATOR . 'TarsServant';
        if (!is_dir($servantPath)) {
            exec('mkdir '. $servantPath);
        }
        $tarsFiles = self::getTarsFiles();
        // TODO 解析tars文件 生成client
    }

    public static function getTarsFiles($is_server = false): array
    {
        $tarsPath = self::getTarsPath();
        $files = self::getFileName($tarsPath);
        $tarsProto = self::getTarsProto();
        $tarsFiles = [];
        $i = 0;
        foreach ($files as $file){
            $pathinfo = pathinfo($file);
            if ($is_server && ($pathinfo['filename'] === "{$tarsProto['appName']}.{$tarsProto['serverName']}")) {
                continue;
            }
            $tarsFiles[$i]['file'] = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['basename'];
            $tarsFiles[$i]['filename'] = $pathinfo['filename'];
            $filename_arr = explode('.', $pathinfo['filename']);
            if (count($filename_arr) !== 2) self::err('tars file name illegal : ' . $tarsFiles[$i]['file']);
            $tarsFiles[$i]['appName'] = $filename_arr[0];
            $tarsFiles[$i]['serverName'] = $filename_arr[1];
            $i++;
        }
        return $tarsFiles;
    }

    public static function getFileName($path): array
    {
        $fileArr = [];
        foreach (glob($path . DIRECTORY_SEPARATOR . '*') as $file) {
            if (is_dir($file)) {
                continue;
            }
            if (preg_match('/\.tars$/', $file)) {
                $fileArr[] = $file;
            }
        }
        return $fileArr;
    }

    public static function getTarsProto()
    {
        $tarsProtoFile = self::getTarsPath() . DIRECTORY_SEPARATOR . 'tars.proto.php';
        if (!is_file($tarsProtoFile)) {
            self::err('is not file : ' . $tarsProtoFile);
        }
        self::log('use tars.proto.php : ' . $tarsProtoFile);

        $tarsProto = require_once $tarsProtoFile;

        if (!isset($tarsProto['appName']) || !isset($tarsProto['serverName']) || !isset($tarsProto['objNames'])) {
            self::err('please set appName/serverName/objName');
        }

        if (!$tarsProto['appName'] || !$tarsProto['serverName'] || !$tarsProto['objNames']) {
            self::err('appName/serverName/objNames empty');
        }

        return $tarsProto;
    }

    public static function getTarsPath(): string
    {
        $tarsPHPRoot = self::getTarsPHPRoot();
        return $tarsPHPRoot . DIRECTORY_SEPARATOR . 'tars';
    }

    public static function getServerPath(): string
    {
        $tarsPHPRoot = self::getTarsPHPRoot();
        $tarsProto = self::getTarsProto();
        return $tarsPHPRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $tarsProto['serverName'];
    }

    public static function getTarsPHPRoot(): string
    {
//        $tarsPHPRoot = dirname(__FILE__, 6);
        $tarsPHPRoot = dirname(__FILE__, 2) . '/dev';
        if (!is_dir($tarsPHPRoot)) {
            self::err('is not dir : ' . $tarsPHPRoot);
        }
        return $tarsPHPRoot;
    }

    public static function err($info)
    {
        self::log('err: ' . $info);
        exit();
    }

    public static function log($info)
    {
        print_r('>>>> ' . $info . "\r\n");
    }
}