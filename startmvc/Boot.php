<?php
/**
 * StartMVC超轻量级PHP开发框架
 *
 * @author    Shao Bing QQ858292510
 * @copyright Copyright (c) 2020-2021
 * @license   StartMVC 遵循Apache2开源协议发布，需保留开发者信息。
 * @link      http://startmvc.com
 */
 
namespace Startmvc;
class Boot
{
    public $conf;
    public function __construct()
    {
        $this->conf = include '../config/common.php';
    }
    public function run()
    {
        if (phpversion() < 7) {
            die('程序要求PHP7+环境版本，当前环境为PHP' . phpversion() . ',请升级服务器环境');            
        }
        //脚本运行开始时间
		$GLOBALS['script_start_time'] = microtime(true);
		
        session_start();
        date_default_timezone_set($this->conf['timezone']);
        if ($this->conf['debug']) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }
        $this->loadFunction();
        $this->getRoute();

    }
    private function loadFunction($dirPath = '../function/')
    {
        if ($dir = opendir($dirPath)) {
            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..') {
                    $filePath = $dirPath . $file;
                    if (is_file($filePath)) {
                        require_once($filePath);
                    } else {
                        $this->loadFunction($filePath . '/');
                    }
                }
            }
        }
    }
    private function getRoute()
    {
        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : (isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''));
        $pathInfo = str_replace('/index.php', '', mb_convert_encoding($pathInfo, 'UTF-8', 'GBK'));
        $pathInfo = str_replace($this->conf['url_suffix'], '', substr($pathInfo, 1));
        $route = include('../config/route.php');
        foreach ($route as $r) {
            $pathInfo = preg_replace($r[0], $r[1], $pathInfo);
        }
        $pathInfo = explode('/', $pathInfo);
        if ($this->conf['muti_module']) {
            $pathInfo[0] = isset($pathInfo[0]) && $pathInfo[0] != '' ? $pathInfo[0] : $this->conf['default_module'];
            $pathInfo[1] = isset($pathInfo[1]) && $pathInfo[1] != '' ? $pathInfo[1] : $this->conf['default_controller'];
            $pathInfo[2] = isset($pathInfo[2]) && $pathInfo[2] != '' ? $pathInfo[2] : $this->conf['default_action'];
            define('MODULE', ucfirst($pathInfo[0]));
            define('CONTROLLER', ucfirst($pathInfo[1]));
            define('ACTION', $pathInfo[2]);
            $argv = array_slice($pathInfo, 3);
        } else { 
            $pathInfo[0] = isset($pathInfo[0]) && $pathInfo[0] != '' ? $pathInfo[0] : $this->conf['default_controller'];
            $pathInfo[1] = isset($pathInfo[1]) && $pathInfo[1] != '' ? $pathInfo[1] : $this->conf['default_action'];
            define('MODULE', '');
            define('CONTROLLER', ucfirst($pathInfo[0]));
            define('ACTION', $pathInfo[1]);
            $argv = array_slice($pathInfo, 2);
        }
        for ($i = 0; $i < count($argv); $i++) {
            $argv[$i] = strip_tags(htmlspecialchars(stripslashes($argv[$i])));
        }
        $this->startApp(MODULE, CONTROLLER, ACTION, $argv);
    }
    private function startApp($module, $controller, $action, $argv) {
        $controller = APP_NAMESPACE.'\\' . ($module != '' ? $module . '\\' : '') . 'Controller\\' . $controller . 'Controller';
        if (!class_exists($controller)) {
            header("HTTP/1.1 404 Not Found");  
            header("Status: 404 Not Found");
            die();
        }
        $action .= 'Action';
        Di::make($controller, $action, $argv);
    }



}