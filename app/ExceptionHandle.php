<?php
namespace app;

use app\common\util\AdminException;
use app\common\util\MobileException;
use app\common\util\ApiException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        //获取模块名称
        $module_name=app('http')->getName();
        // 添加自定义异常处理机制
        // 后台参数验证错误拦截
        if ($e instanceof ValidateException && (!empty($module_name) && $module_name=='admin')) {
            $error = $e->getError();
            $values=array_values($error);
            return json(['code'=>10,'msg'=>$values[0],'result'=>$error]);
        } elseif ($e instanceof ValidateException && (!empty($module_name) && $module_name == 'mobile')) {
            //前台参数验证错误拦截
            $error=$e->getError();
            return json(['code'=>0,'msg'=>$error]);
        }elseif ($e instanceof ValidateException && (!empty($module_name) && $module_name == 'api')) {
            //接口参数验证错误拦截
            $error=$e->getError();
            return json(['code'=>400,'msg'=>$error]);
        }
//        // 请求异常
//        if ($e instanceof HttpException && $request->isAjax()) {
//            $this->write($e->getMessage());
//            return response($e->getMessage(), $e->getStatusCode());
//        }
        if ($e instanceof ApiException){
            return json(['code'=>$e->getCode(),'msg'=>$e->getMessage(),'result'=>$e->getArr()]);
        }
        if ($e instanceof AdminException || $e instanceof MobileException){
            return json(['code'=>$e->getCode(),'msg'=>$e->getMessage(),'result'=>$e->getArr()]);
        }
        $this->write($e->getMessage(),$e->getFile(),$e->getLine());
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
    private function write($info,$file,$line){
        if (is_array($info)) {
            $info = json_encode($info);
        }
        $content = sprintf("%s %s %s 文件:%s %s行 \n", date('Y-m-d H:i:s'),'系统错误:', $info,$file,$line);
        $file  = root_path()."log/error.txt";//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file),0755,true);
        }
        file_put_contents($file,$content,FILE_APPEND);
    }
}
