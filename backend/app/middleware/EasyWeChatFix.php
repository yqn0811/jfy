<?php

namespace app\middleware;

class EasyWeChatFix
{
    public function handle($request, \Closure $next)
    {
        // 在应用启动时修复 EasyWeChat
        $this->fixEasyWeChatCollection();
        
        return $next($request);
    }
    
    protected function fixEasyWeChatCollection()
    {
        $collectionFile = __DIR__ . '/../common/EasyWeChatCollectionFix.php';
        
        if (!class_exists('EasyWeChat\Kernel\Support\Collection', false)) {
            // 预加载修复后的类
            require_once $collectionFile;
        }
    }
}