<?php
declare (strict_types = 1);

namespace app\middleware;

use app\common\service\CorsOriginService;

class Cors
{
    public function handle($request, \Closure $next)
    {
        $origin = CorsOriginService::resolveAllowedOrigin((string)$request->header('origin', ''));
        $allowCredentials = CorsOriginService::allowCredentials($origin) ? 'true' : 'false';
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, Authorization-Token');
        header('Access-Control-Allow-Credentials: ' . $allowCredentials);
        header('Vary: Origin');
        if (strtoupper($request->method()) === 'OPTIONS') {
            return response('', 204);
        }
        $response = $next($request);
        if (method_exists($response, 'header')) {
            $response->header([
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Accept, Authorization, Authorization-Token',
                'Access-Control-Allow-Credentials' => $allowCredentials,
                'Vary' => 'Origin',
            ]);
        }
        return $response;
    }
}
