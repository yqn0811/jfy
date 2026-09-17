<?php
// +----------------------------------------------------------------------
// | 萤火商城系统 [ 致力于通过产品和服务，帮助商家高效化开拓市场 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2024 https://www.yiovo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
// +----------------------------------------------------------------------
// | Author: 萤火科技 <admin@yiovo.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace cores;

use Throwable;
use think\Response;
use think\response\Json;
use think\facade\Log;
use think\facade\Request;
use think\exception\Handle;
use think\db\exception\PDOException;
use think\exception\HttpResponseException;
use cores\exception\BaseException;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    // 状态码
    private $status = 200;

    // 错误信息
    private $message;

    // 附加数据
    public $data = [];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 不使用内置的方式记录异常日志
        // parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }
        $this->errorLog($e);
        // 手动触发的异常 BaseException
        if ($e instanceof BaseException) {
            $this->status = $e->status;
            $this->message = $e->message;
            $this->data = $e->data;
            $extend = property_exists($e, 'extend') ? $e->extend : [];
            return $this->output($extend);
        }
        $app_name = app('http')->getName();
        if($app_name == 'index'){
            $type = \request()->isAjax();
            if($type){
                $this->status = 0;
                $this->message = $e->getMessage();
                return $this->output();
            }
            $result = [
                'code' => 0,
                'msg' => $e->getMessage(),
                'data' => '',
                'url' => 'javascript:history.back(-1);',
                'wait' => 3,
            ];
            $response = Response::create($this->app->config->get('jump.dispatch_error_tmpl'), 'view')->assign($result)->header([]);
            throw new HttpResponseException($response);
        }
        if($app_name == 'api'){
            if(app()->isDebug()){
                $this->status = 500;
                $this->message = $e->getMessage();
                return $this->outputDebug($e);
            }
            throwError($e->getMessage());
        }
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

    /**
     * 返回json格式数据
     * @param array $extend 扩展的数据
     * @return Json
     */
    private function output(array $extend = []): Json
    {
        $jsonData = ['msg' => $this->message, 'code' => $this->status, 'data' => $this->data];
        return json(array_merge($jsonData, $extend));
    }

    /**
     * 返回json格式数据 (debug模式)
     * @param Throwable $e
     * @return Json
     */
    private function outputDebug(Throwable $e): Json
    {
        $debug = [
            'name' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $this->getCode($e),
            'message' => $this->getMessage($e),
            'trace' => $e->getTrace(),
            'source' => $this->getSourceCode($e),
        ];
        return $this->output(['debug' => $debug]);
    }

    /**
     * 将异常写入日志
     * @param Throwable $e
     */
    private function errorLog(Throwable $e)
    {
        // 错误信息
        $data = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $this->getMessage($e),
            'status' => $this->getCode($e),
        ];
        // 日志内容
        $log = $this->getVisitor();
        $log .= "\r\n" . "[ message ] [{$data['status']}] {$data['message']}";
        $log .= "\r\n" . "[ file ] {$data['file']}:{$data['line']}";
        // $log .= "\r\n" . "[ time ] " . format_time(time());
        $log .= "\r\n" . '[ header ] ' . print_r(Request::header(), true);
        $log .= '[ param ] ' . print_r(Request::param(), true);
        // 如果是数据库报错, 则记录sql语句
        if ($e instanceof PDOException) {
            $log .= "[ Error SQL ] " . $e->getData()['Database Status']['Error SQL'];
            $log .= "\r\n";
        }
        $log .= "\r\n" . $e->getTraceAsString();
        $log .= "\r\n" . '--------------------------------------------------------------------------------------------';
        // 写入日志文件
        Log::record($log, 'error');
    }

    /**
     * 获取请求路径信息
     * @return string
     */
    private function getVisitor(): string
    {
        $data = [Request::ip(), Request::method(), Request::url(true)];
        return implode(' ', $data);
    }
}
