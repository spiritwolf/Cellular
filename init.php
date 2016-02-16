<?php
/**
 *
 * Cellular Framework
 *
 * @author mark weixuan.1987@hotmail.com
 * @version 1.0 2015-12-9
 *
 */

class Cellular
{

	private static $frameworkPath; //框架根目录
	private static $appPath; //应用程序根目录
	private static $webRootPath; //web根目录
	private static $assetsPath; //静态资源目录
	private static $config; //应用配置文件
	private static $URI; //URI请求资源
	private static $errorMsg; //web访问错误信息

	//实例化的对象
	private static $classes = array();
	//应用程序结构体
	public static $appStruct = array(
		'controller' => 'controller',
		'model' => 'model',
		'view' => 'view',
		'config' => 'config',
		'assets' => 'assets'
	);

	/**
	 * 自动加载类文件路径
	 * @param string $className 类名称 lib.loader
	 * @return boolean true|false
	 */
	public static function autoload($className)
	{
		$className = mb_strtolower(strtr($className, '\\', DIRECTORY_SEPARATOR)).'.php';
		return self::loadFile($className);
	}

	/**
	 * 设置调试环境
	 * @param string $environment 环境状态
	 * @return void
	 */
	public static function debug($environment)
	{
		switch ($environment)
		{
			//开发环境
			case 'development':
				ini_set("display_errors",'on');
				error_reporting(E_ALL);
				break;
			//测试环境
			case 'testing':
				break;
			//生产环境
			case 'production':
				ini_set('display_errors', 'off');
				error_reporting(0);
				break;
		}
	}

	/**
	 * 框架主入口 执行应用程序
	 */
	public static function application($path = null)
	{

		self::$frameworkPath = dirname(__FILE__).DIRECTORY_SEPARATOR;
		self::$appPath = ($path == null) ? './' : $path;
		//获取uri
		if (self::parseURI()) {
			//加载应用配置文件
			self::$config = self::config('app');
			//定义静态资源常量
			define('STARTTIME', microtime(true));
			define('WEBROOTPATH', self::$webRootPath);
			define('ASSETS', empty(self::$config['assets_path']) ? self::$assetsPath.DIRECTORY_SEPARATOR.self::$appStruct['assets'] : self::$config['assets_path']);
			//设置默认时区
			if (isset(self::$config['timezone'])) date_default_timezone_set(self::$config['timezone']);
			//控制器转发
			if (!self::hub()) {
				self::error(self::$errorMsg['code'], self::$errorMsg['msg']);
			}
		} else {
			self::error(self::$errorMsg['code'], self::$errorMsg['msg']);
		}
	}

	private static function parseURI()
	{
		//获取请求资源ID
		$requestURI = isset($_GET['uri']) ? $_GET['uri'] : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
		//获取web根目录，当应用入口不在web根目录时有效
		self::$webRootPath = substr($_SERVER['DOCUMENT_URI'], 0, strrpos($_SERVER['DOCUMENT_URI'], '/'));
		if (!empty(self::$webRootPath)) {
			$requestURI = str_replace(self::$webRootPath, '', $requestURI); //过滤脚本目录
			self::$assetsPath = self::$webRootPath;
		}
		//请求资源检查
		if (!preg_match("/^[A-Za-z0-9_\-\/.%&#@]+$/", $requestURI) && !empty($requestURI)) {
			self::$errorMsg = array(
				'code' => '400',
				'msg' => 'URI not allowed!'
			);
			return false;
		}
		//解析请求URI字符串
		if ($requestURI != '') {
			//过滤GET参数
			$removeParamURI = substr($requestURI, 0, strpos($requestURI, '?'));
			$requestURI = isset($removeParamURI{0}) ? $removeParamURI : $requestURI;
			//字符串转数组
			$request = explode('/', $requestURI);
			//去除空数组
			$request = array_filter($request);
			//获取应用名，当一个入口下有多个应用时有效
			foreach ($request as $key => $value) {
				if (!is_dir(self::$appPath.$value)) break;
				self::$appPath .= $value.DIRECTORY_SEPARATOR;
				self::$webRootPath .= DIRECTORY_SEPARATOR.$value;
				self::$assetsPath .= DIRECTORY_SEPARATOR.$value;
				unset($request[$key]);
			}
			self::$URI = $request;
		}
		return true;
	}

	/**
	 * 控制器转发
	 */
	private static function hub()
	{
		$controller = 'Index';
		$action = 'main';
		//解析控制器与动作参数
		if (self::$URI) {
			//获取控制器
			$request = self::$URI;
			$controller = '';
			$controllerDir = self::$appPath.self::$appStruct['controller'];
			foreach ($request as $key => $value) {
				$controller .= DIRECTORY_SEPARATOR.$value;
				unset($request[$key]);
				if (!is_dir($controllerDir.$controller)) break;
			}
			$controller = strtr(substr($controller, 1), DIRECTORY_SEPARATOR, '.');
			//获取动作
			if ($request) {
				$action = array_shift($request);
			}
		}
		//检查动作名是否安全-防注入
		if (!preg_match("/^[A-Za-z0-9_]+$/", $controller)) {
			self::$errorMsg = array(
				'code' => '400',
				'msg' => 'Controller not allowed!'
			);
			return false;
		}
		if (!preg_match("/^[A-Za-z0-9_]+$/", $action)) {
			self::$errorMsg = array(
				'code' => '400',
				'msg' => 'Action not allowed!'
			);
			return false;
		}
		//加载控制器执行动作
		$class = self::loadClass(self::$appStruct['controller'].'.'.$controller);
		if (false !== $class) {
			if(method_exists($class, $action)) {
				$class->$action();
				return true;
			}
		}
		self::$errorMsg = array(
			'code' => '404',
			'msg' => 'Page not Found!'
		);
		return false;
	}

	/**
	 * 请求错误提示
	 */
	private static function error($code, $msg = null)
	{
		$request = self::getFile('error/400.html');
		$var = array('<header></header>','<p></p>');
		switch ($code) {
			case '400':
				$value = array('<header>400</header>','<p>'.$msg.'</p>');
				$request = str_replace($var, $value, $request);
				break;
			case '404':
				$value = array('<header>404</header>','<p>'.$msg.'</p>');
				$request = str_replace($var, $value, $request);
				break;
		}
		die($request);
	}

	/**
	 * 读取文件
	 */
	public static function getFile($file)
	{
		if ($path = self::getFilePath($file)) {
			return file_get_contents($path);
		}
		return false;
	}

	/**
	 * 加载文件
	 */
	public static function loadFile($file)
	{
		if ($path = self::getFilePath($file)) {
			return include_once($path);
		}
		return false;
	}

	/**
	 * 加载配置
	 */
	public static function config($name)
	{
		$path = self::$appStruct['config'].DIRECTORY_SEPARATOR.$name.'.php';
		return self::loadFile($path);
	}

	/**
	 * 加载模型
	 */
	public static function model($name)
	{
		$model = self::loadClass(self::$appStruct['model'].'.'.$name, $name);
		if ($model != false) return $model;
		return self::loadClass('core.model', $name);
	}

	/**
	 * 加载试图
	 */
	public static function view($name, $value)
	{
		if ($value) extract($value);
		$path = self::$appStruct['view'].DIRECTORY_SEPARATOR.$name.'.php';
		if ($path = self::getFilePath($path)) {
			return include_once($path);
		}
		return false;
	}

	/**
	 * 获取文件路径
	 */
	private static function getFilePath($file)
	{
		//检查文件名是否安全-防注入
		if (preg_match("/^[A-Za-z0-9_\-\/.]+$/", $file)) {
			//解析文件路径
			$path = self::$appPath.$file;
			if (is_file($path)) return $path;
			//搜索Cellular目录－包含命名空间
			$path = self::$frameworkPath.$file;
			if (is_file($path)) return $path;
		}
		return false;
	}

	/**
	 * 装载类
	 */
	public static function loadClass($className, $param = null)
	{
		//检查类名是否安全-防注入
		if (preg_match("/^[A-Za-z0-9_.]+$/", $className)) {
			//检查是否已实例化
			if (isset(self::$classes[$className])) return self::$classes[$className];
			//实例化类
			$class = '\\'.strtr($className, '.', '\\'); //解析类名
			if (class_exists($class)) {
				return self::$classes[$className] = new $class($param);
			}
		}
		return false;
	}

	/**
	 * 卸载类
	 */
	public static function remvoeClass($className)
	{
		//检查类名是否安全-防注入
		if (!preg_match("/^[A-Za-z0-9_.]+$/", $className)) {
			if (isset(self::$classes[$className])) {
				unset(self::$classes[$className]);
				return true;
			}
		}
		return false;
	}

}

spl_autoload_register(array('Cellular', 'autoload'));

?>
