<?php
/**
 * Created by PhpStorm.
 * User: liangchen
 * Date: 2018/2/11
 * Time: 下午2:25.
 */

namespace Imi\Tars;

class Utils
{
    // tars.tarsregistry.QueryObj@tcp -h 127.0.0.1 -p 17890:tcp -h 127.0.0.1 -p 17890:tcp -h 127.0.0.1 -p 17890
    public static function getLocatorInfo($locatorString)
    {
        $parts = explode('@', $locatorString);
        $locatorName = $parts[0];

        $subParts = explode(':', $parts[1]);
        $infos = [];
        foreach ($subParts as $subPart) {
            $info = self::getEndpointInfo($subPart);
            $infos[] = $info;
        }

        return [
            'locatorName' => $locatorName,
            'routeInfo' => $infos,
        ];
    }

    // 解析出node上报的配置 tars.tarsnode.ServerObj@tcp -h 127.0.0.1 -p 2345 -t 10000
    public static function parseNodeInfo($nodeInfo)
    {
        $parts = explode('@', $nodeInfo);
        $objName = $parts[0];
        $subParts = explode('-', $parts[1]);
        $mode = trim($subParts[0]);
        $host = trim($subParts[1], 'h ');
        $port = trim($subParts[2], 'p ');
        $timeout = trim($subParts[3], 't ') / 1000;

        return [
            'objName' => $objName,
            'mode' => $mode,
            'host' => $host,
            'port' => $port,
            'timeout' => $timeout,
            'sIp' => $host,
            'iPort' => $port,
        ];
    }

    // tcp -h 127.0.0.1 -p 17890
    // tcp -h tars-tarsregistry -p 17890
    public static function getEndpointInfo($endpoint)
    {
        $endpoint = trim($endpoint, " \n\r\t");
        $parts = explode(' ', $endpoint);
        $sHost = '';
        $sProtocol = '';
        $iPort = '';
        $iTimeout = '';
        $bIp = '';
        $index = 0;
        while ($index < count($parts)) {
            // endpoint info empty
            if(empty($parts) || count($parts) == 0)
                break;
            $part = $parts[$index];
            if (strstr($part, 'tcp')) {
                $sProtocol = 'tcp';
                $index++;
                continue;
            } elseif (strstr($part, 'udp')) {
                $sProtocol = 'udp';
                $index++;
                continue;
            }

            if(strpos($part, '-h') !== false) {
                // greedy
                if(array_key_exists($index+1, $parts)) {
                    $hostPart = $parts[$index+1];
                    $sHost = trim($hostPart, " \t\r\n");
                    $index++;
                }
            }
            elseif(strpos($part, '-b') !== false) {
                // greedy
                if(array_key_exists($index+1, $parts)) {
                    $ipPart = $parts[$index+1];
                    $bIp = trim($ipPart, " \t\r\n");
                    $index++;
                }
            }
            elseif(strpos($part, '-p') !== false) {
                // greedy
                if(array_key_exists($index+1, $parts)) {
                    $portPart = $parts[$index+1];
                    $iPort = trim($portPart, " \t\r\n");
                    $index++;
                }
            }
            elseif(strpos($part, '-t') !== false) {
                // greedy
                if(array_key_exists($index+1, $parts)) {
                    $timeoutPart = $parts[$index+1];
                    $iTimeout = trim($timeoutPart, " \t\r\n");
                    $index++;
                }
            }
            $index++;
        }

        return [
            'sHost' => $sHost,
            'sProtocol' => $sProtocol,
            'iPort' => $iPort,
            'iTimeout' => $iTimeout,
            'bIp' => $bIp,
            'sIp' => $sHost,
        ];
    }

    public static function parseFile($configPath)
    {
        $text = file_get_contents($configPath);

        if (empty($text)) {
            echo ' start FAIL, config file missing';
            exit;
        }

        return self::parseText($text);
    }

    public static function parseText($text): array
    {
        $tarsAdapters = [];
        $tarsServer = [];
        $tarsClient = [];
        $objAdapter = [];
        $application = [];
        $lines = explode("\n", $text);

        $status = 0; //标识在application内
        foreach ($lines as $line) {
            $line = trim($line, " \r\0\x0B\t\n");
            if (empty($line)) {
                continue;
            }
            if ($line[0] == '#') {
                continue;
            }

            switch ($status) {
                case 0:{
                    if (strstr($line, '<server>')) {
                        $status = 1;
                    } elseif (strstr($line, '<client>')) {
                        $status = 3;
                    } elseif (strstr($line, '=')) {
                        $pos = strpos($line, '=');
                        $name = substr($line, 0, $pos);
                        $value = substr($line, $pos + 1, strlen($line) - $pos);
                        $application[$name] = $value;
                    }
                    break;
                }
                // 在server内
                case 1:{
                    if (strstr($line, '=')) {
                        $pos = strpos($line, '=');
                        $name = substr($line, 0, $pos);
                        $value = substr($line, $pos + 1, strlen($line) - $pos);
                        $tarsServer[$name] = $value;
                    }
                    // 还要兼容多个adapter的情况(终止的时候兼容即可)
                    elseif (strstr($line, 'Adapter>')) {
                        $status = 2;
                        $adapterName = substr($line, 0, strlen($line) - 1);
                        $adapterName = substr($adapterName, 1, strlen($line) - 1);
                        $objAdapter['adapterName'] = $adapterName;
                    } elseif (strstr($line, '<client>')) {
                        $status = 3;
                    }
                    break;
                }
                // 在adapter内
                case 2: {
                    if (strstr($line, '=')) {
                        $pos = strpos($line, '=');
                        $name = substr($line, 0, $pos);
                        $value = substr($line, $pos + 1, strlen($line) - $pos);
                        $objAdapter[$name] = $value;
                    }
                    // 还要兼容多个adapter的情况(终止的时候兼容即可)
                    elseif (strstr($line, '</')) {
                        $tarsAdapters[] = $objAdapter;
                        $objAdapter = [];
                        $status = 1;
                    }
                    break;
                }
                // 在client内
                case 3: {
                    if (strstr($line, '=')) {
                        $pos = strpos($line, '=');
                        $name = substr($line, 0, $pos);
                        $value = substr($line, $pos + 1, strlen($line) - $pos);
                        $tarsClient[$name] = $value;
                    }
                    // 还要兼容多个adapter的情况(终止的时候兼容即可)
                    elseif (strstr($line, '</client>')) {
                        $status = 0;
                    }
                    break;
                }
                default: {
                    break;
                }
            }
        }

        //把not_tars协议的排序到最前面
        usort($tarsAdapters, function ($rowOne, $rowTwo) {
            if ($rowOne['protocol'] == 'not_tars' || $rowOne['protocol'] == 'not_taf') {
                return -1;
            }
            if ($rowTwo['protocol'] == 'not_tars' || $rowTwo['protocol'] == 'not_taf') {
                return 1;
            }
            return -1;
        });

        foreach ($tarsAdapters as $key => $tarsAdapter) {
            $tmp = self::getEndpointInfo($tarsAdapter['endpoint']);
            $tarsServer['listen'][] = $tmp;
            $tarsAdapters[$key]['listen'] = $tmp;
            $tarsAdapters[$key]['objName'] = explode('.', $tarsAdapter['servant'])[2];
        }

        $tarsServer['entrance'] = $tarsServer['entrance'] ?? $tarsServer['basepath'] . 'src/index.php';
        $setting['worker_num'] = max(array_column($tarsAdapters, 'threads'));
        $setting['task_worker_num'] = $tarsServer['task_worker_num'];
        $setting['dispatch_mode'] = $tarsServer['dispatch_mode'];
        $setting['daemonize'] = $tarsServer['daemonize'];

        if (isset($tarsServer['reactor_num'])) {
            $setting['reactor_num'] = $tarsServer['reactor_num'];
        }
        if (isset($tarsServer['max_request'])) {
            $setting['max_request'] = $tarsServer['max_request'];
        }
        if (isset($tarsServer['max_conn'])) {
            $setting['max_conn'] = $tarsServer['max_conn'];
        }
        if (isset($tarsServer['task_worker_num'])) {
            $setting['task_worker_num'] = $tarsServer['task_worker_num'];
        }
        if (isset($tarsServer['task_ipc_mode'])) {
            $setting['task_ipc_mode'] = $tarsServer['task_ipc_mode'];
        }
        if (isset($tarsServer['task_max_request'])) {
            $setting['task_max_request'] = $tarsServer['task_max_request'];
        }
        if (isset($tarsServer['task_tmpdir'])) {
            $setting['task_tmpdir'] = $tarsServer['task_tmpdir'];
        }
        if (isset($tarsServer['dispatch_func'])) {
            $setting['dispatch_func'] = $tarsServer['dispatch_func'];
        }
        if (isset($tarsServer['message_queue_key'])) {
            $setting['message_queue_key'] = $tarsServer['message_queue_key'];
        }
        if (isset($tarsServer['backlog'])) {
            $setting['backlog'] = $tarsServer['backlog'];
        }
        if (isset($tarsServer['loglevel'])) {
            $setting['log_level'] = $tarsServer['loglevel'];
        }
        if (isset($tarsServer['heartbeat_check_interval'])) {
            $setting['heartbeat_check_interval'] = $tarsServer['heartbeat_check_interval'];
        }
        if (isset($tarsServer['heartbeat_idle_time'])) {
            $setting['heartbeat_idle_time'] = $tarsServer['heartbeat_idle_time'];
        }
        if (isset($tarsServer['open_eof_check'])) {
            $setting['open_eof_check'] = $tarsServer['open_eof_check'];
        }
        if (isset($tarsServer['open_eof_split'])) {
            $setting['open_eof_split'] = $tarsServer['open_eof_split'];
        }
        if (isset($tarsServer['package_eof'])) {
            $setting['package_eof'] = $tarsServer['package_eof'];
        }
        if (isset($tarsServer['open_length_check'])) {
            $setting['open_length_check'] = $tarsServer['open_length_check'];
        }
        if (isset($tarsServer['package_length_type'])) {
            $setting['package_length_type'] = $tarsServer['package_length_type'];
        }
        if (isset($tarsServer['package_length_offset'])) {
            $setting['package_length_offset'] = $tarsServer['package_length_offset'];
        }
        if (isset($tarsServer['package_body_offset'])) {
            $setting['package_body_offset'] = $tarsServer['package_body_offset'];
        }
        if (isset($tarsServer['package_length_func'])) {
            $setting['package_length_func'] = $tarsServer['package_length_func'];
        }
        if (isset($tarsServer['package_max_length'])) {
            $setting['package_max_length'] = $tarsServer['package_max_length'];
        }
        if (isset($tarsServer['open_cpu_affinity'])) {
            $setting['open_cpu_affinity'] = $tarsServer['open_cpu_affinity'];
        }
        if (isset($tarsServer['open_tcp_nodelay'])) {
            $setting['open_tcp_nodelay'] = $tarsServer['open_tcp_nodelay'];
        }
        if (isset($tarsServer['buffer_output_size'])) {
            $setting['buffer_output_size'] = $tarsServer['buffer_output_size'];
        }
        if (isset($tarsServer['tcp_defer_accept'])) {
            $setting['tcp_defer_accept'] = $tarsServer['tcp_defer_accept'];
        }
        if (isset($tarsServer['ssl_cert_file'])) {
            $setting['ssl_cert_file'] = $tarsServer['ssl_cert_file'];
        }
        if (isset($tarsServer['ssl_method'])) {
            $setting['ssl_method'] = $tarsServer['ssl_method'];
        }
        if (isset($tarsServer['ssl_ciphers'])) {
            $setting['ssl_ciphers'] = $tarsServer['ssl_ciphers'];
        }
        if (isset($tarsServer['user'])) {
            $setting['user'] = $tarsServer['user'];
        }
        if (isset($tarsServer['group'])) {
            $setting['group'] = $tarsServer['group'];
        }
        if (isset($tarsServer['chroot'])) {
            $setting['chroot'] = $tarsServer['chroot'];
        }
        if (isset($tarsServer['pid_file'])) {
            $setting['pid_file'] = $tarsServer['pid_file'];
        }
        if (isset($tarsServer['pipe_buffer_size'])) {
            $setting['pipe_buffer_size'] = $tarsServer['pipe_buffer_size'];
        }
        if (isset($tarsServer['buffer_output_size'])) {
            $setting['buffer_output_size'] = $tarsServer['buffer_output_size'];
        }
        if (isset($tarsServer['socket_buffer_size'])) {
            $setting['socket_buffer_size'] = $tarsServer['socket_buffer_size'];
        }
        if (isset($tarsServer['enable_unsafe_event'])) {
            $setting['enable_unsafe_event'] = $tarsServer['enable_unsafe_event'];
        }
        if (isset($tarsServer['discard_timeout_request'])) {
            $setting['discard_timeout_request'] = $tarsServer['discard_timeout_request'];
        }
        if (isset($tarsServer['enable_reuse_port'])) {
            $setting['enable_reuse_port'] = $tarsServer['enable_reuse_port'];
        }
        if (isset($tarsServer['enable_delay_receive'])) {
            $setting['enable_delay_receive'] = $tarsServer['enable_delay_receive'];
        }
        if (isset($tarsServer['open_http_protocol'])) {
            $setting['open_http_protocol'] = $tarsServer['open_http_protocol'];
        }
        if (isset($tarsServer['open_http2_protocol'])) {
            $setting['open_http2_protocol'] = $tarsServer['open_http2_protocol'];
        }
        if (isset($tarsServer['open_websocket_protocol'])) {
            $setting['open_websocket_protocol'] = $tarsServer['open_websocket_protocol'];
        }
        if (isset($tarsServer['open_mqtt_protocol'])) {
            $setting['open_mqtt_protocol'] = $tarsServer['open_mqtt_protocol'];
        }
        if (isset($tarsServer['reload_async'])) {
            $setting['reload_async'] = $tarsServer['reload_async'];
        }
        if (isset($tarsServer['tcp_fastopen'])) {
            $setting['tcp_fastopen'] = $tarsServer['tcp_fastopen'];
        }
        if (isset($tarsServer['request_slowlog_file'])) {
            $setting['request_slowlog_file'] = $tarsServer['request_slowlog_file'];
        }

        $setting['log_file'] = $tarsServer['logpath'].$tarsServer['app'].'/'.$tarsServer['server'].'/'.
            $tarsServer['app'].'.'.$tarsServer['server'].'.log';
        $setting['log_path'] = $tarsServer['logpath'];

        $tarsServer['adapters'] = $tarsAdapters;
        $tarsServer['setting'] = $setting;

        $application['server'] = $tarsServer;
        $application['client'] = $tarsClient;

        return [
            'tars' => [
                'application' => $application,
            ],
        ];
    }

    public static function getTarsConf()
    {
        return Conf::get();
    }
}
