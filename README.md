<h1>Cellular</h1>
<p>Cellular是一个超轻量级的WEB开发框架，完全使用PHP语言编写，框架由一个核心文件和一些基本类库组成。</p>

<h3>基本功能</h3>
<ul>
  <li>支持MVC开发模式</li>
  <li>支持自动加载类</li>
  <li>支持数据库ORM操作</li>
  <li>实现单一入口</li>
</ul>

<h3>引用Cellular框架</h3>

<p><h5>方法说明</h5><p>
<pre>
# 引入框核心文件
include('/Cellular/init.php');

# 设置调试状态
# development: 开发模式(显示错误信息)
# production: 生产模式(不显示错误信息)
Cellular::debug('development');

# 启动应用
# path: 如果应用程序在we服务器的文件夹中，此参数可以为空
Cellular::application($path);
</pre>

<p><h5>引用示例</h5><p>
<pre>
# <b>示例1: 一个入口文件对应一个应用</b>
# nginx配置
if (!-f $request_filename) {
    rewrite ^/(.*) /app1/index.php?uri=$1 last;
}
# 入口文件
include('../Cellular/init.php');
Cellular::application();

# <b>示例2: 一个入口文件对应一个应用，应用程序在web服务器目录之外</b>
# nginx配置
if (!-f $request_filename) {
    rewrite ^/(.*) /app1/index.php?uri=$1 last;
}
# 入口文件
include('Cellular/init.php');
Cellular::application('/opt/app1');

# <b>示例3: 一个入口文件对应多个应用</b>
# nginx配置
if (!-f $request_filename) {
    rewrite ^/(.*) /index.php?uri=$1 last;
}
# 入口文件
include('Cellular/init.php');
Cellular::application();

# <b>示例4: 一个入口文件对应多个应用，应用程序在web服务器目录之外</b>
# nginx配置
if (!-f $request_filename) {
    rewrite ^/(.*) /index.php?uri=$1 last;
}
# 入口文件
include('Cellular/init.php');
Cellular::application('/opt');
</pre>

