<?php
// 应用公共文件

use cores\exception\BaseException;
use think\facade\Db;
use think\cache\driver\Redis;

function throwError(string $message, ?int $status = null, array $data = [], $url = null)
{
    is_null($status) && $status = config('status.param_error');
    throw new BaseException(['status' => $status, 'message' => $message, 'data' => $data, 'url' => $url]);
}

function remote($uniacid, $url, $type)
{
    if(!$url){
        return '';
    }
    $uniacid = 1;
    //过滤系统本地图片还是使用本地路径
    if($type == 1){
        if(strpos($url, '/image/nopic.jpg') !== false){
            return  getLocalImage($url);
        }
        if(strpos($url, '/image/static/footer/') !== false){
            return  getLocalImage($url);
        }
    }
    if($type == 2){
        if(strpos($url, '/image/static/footer/') !== false){
            $host_url = $_SERVER['HTTP_HOST'];
            if(strpos($url, $host_url) !== false){
                $temp_a = explode($host_url, $url);
                return $temp_a[1];
            }
        }
    }

    $back_url = doRemote($uniacid, $url, $type);

    if($type == 1){
        $back_url = $back_url . '?x-oss-process=image/resize,m_fixed,w_480/quality,Q_75';
    }else{
        $back_url = str_replace('?x-oss-process=image/resize,m_fixed,w_480/quality,Q_75', '', $back_url);
    }

    $back_url = str_replace('//upimages', '/upimages', $back_url);
    return normalizePublicAssetUrl($back_url);
}

//远程图片链接处理
function doRemote($uniacid, $url, $type)
{
    $remote_config = cacheRemoteSet($uniacid);
    $remote = $remote_config['remote'];
    $qiniu = $remote_config['qiniu'];
    $aliOss = $remote_config['aliOss'];
    $cos = $remote_config['cos'];
    if ($remote == 1) {
        if ($type == 1) {   //1是取   2是写
            if(strpos($url, ',') !== false){
                $url = explode(',', $url)[0];
            }
            if (strpos($url, 'http') === false) {
                $host_rul = ROOT_HOST;
                $temp_a = explode(":", $host_rul);

                if ($temp_a[0] == 'http') {
                    //$temp_a[0] = 'https';
                    $host_rul = implode(':', $temp_a);
                }

                if (substr($url, 0, 1) == '/') {
                    if (strpos($url, 'addons') === false) {
                        $url = STATIC_ROOT.$url;
                    }
                    $url = $host_rul  .$url;
                } else {
                    if (strpos($url, 'addons') === false) {
                        $url = STATIC_ROOT.'/'.$url;
                    }
                    $url = $host_rul  . '/' . $url;
                }

            } else {
                if (strpos($url, 'addons') === false) {
                    $host_url = $_SERVER['HTTP_HOST'];
                    if(strpos($url, $host_url) !== false){
                        $temp_a = explode($host_url, $url);
                        if (strpos($url, 'upimages') !== false) {
                            $img = '/upimages'.explode('/upimages', $temp_a[1])[1];
                        }else{
                            $img = $temp_a[1];
                        }
                        $url = $temp_a[0].$host_url.STATIC_ROOT.$img;
                    }
                }
                $temp_a = explode(":", $url);
                if ($temp_a[0] == 'http') {
                    $temp_a[0] = 'https';
                    $url = implode(':', $temp_a);
                }
            }
        } else {
            if (strpos($url, 'http') !== false) {
                if (strpos($url, '/addons') !== false || strpos($url, '/upimages') !== false ) {
                    if(strpos($url, '/addons') !== false){
                        $url = "/addons" . explode("/addons", $url)[1];
                    }else{
                        $url = "/upimages" . explode("/upimages", $url)[1];
                    }
                } else if (strpos($url, 'diypage/resource') !== false) {
                    $url = "/diypage/resource" . explode("diypage/resource", $url)[1];
                } else if (strpos($url, 'diyusercenter/resource') !== false) {
                    $url = "/diyusercenter/resource" . explode("diyusercenter/resource", $url)[1];
                }
            }
        }
    } else if ($remote == 2) {
        if ($type == 1) {
            if (strpos($url, 'http') === false) {
                if (strpos($url, '/diypage/img/blank.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default/default_start.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default/tcgg.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default') !== false) {
                    $url = getLocalImage($url);
                }else if (strpos($url, '/diypage/template_img/') !== false) {
                    $url = $url;
                } else if (strpos($url, 'diyusercenter/resource/images/') !== false) {
                    $url = 'https://' . $_SERVER['HTTP_HOST'].$url;
                } else {
                    $url = $qiniu['folder_name'] ? $qiniu['folder_name'] . '/' .ltrim($url, '/') : $url;
                    if(substr($url, 0, 1) == '/'){
                        $url = $qiniu['domain'] . $url;
                    }else{
                        $url = $qiniu['domain'] . '/' . $url;
                    }

                }
            }
            //替换素材为http，防止走https流量
            // if (strpos($url, '.mp4') == false && strpos($url, '.MP4') == false) {
            //     $url = str_replace('https://', 'http://', $url);
            // }else{
            //     $url = str_replace('https://', 'http://', $url);
            // }

            //调整七牛为优先走http，并且配置默认压缩图片
            //$url = str_replace('https://', 'http://', $url).'?imageMogr2/thumbnail/750x/format/jpg/blur/1x0/quality/100';
        } else {
            if (strpos($url, $qiniu['domain']) !== false) {
                if($qiniu['folder_name']){
                    if(strpos($url, $qiniu['domain'].'/'.$qiniu['folder_name']) !== false){
                        $url = explode($qiniu['domain'].'/'.$qiniu['folder_name'], $url)[1];
                    }else{
                        $url = $url;
                    }
                }else{
                    $url = explode($qiniu['domain'], $url)[1];
                }

                while (substr($url, 0, 1) == '/') {
                    $url = substr($url, 1);
                }
            }
        }
    } else if ($remote == 3) {
        if ($type == 1) {
            if (strpos($url, 'http') === false) {
                if (strpos($url, '/diypage/img/blank.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default/default_start.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default/tcgg.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default') !== false) {
                    $url = getLocalImage($url);
                }else if (strpos($url, '/diypage/template_img/') !== false) {
                    $url = $url;
                } else if (strpos($url, 'diyusercenter/resource/images/') !== false) {
                    $url = 'https://' . $_SERVER['HTTP_HOST'].$url;
                }else {
                    if ($aliOss && strpos($aliOss['domain'], "http") !== false) {
                        $url = $aliOss['folder_name'] ? $aliOss['folder_name']. '/' .ltrim($url, '/') : $url;
                        $qianhttp = explode('//', $aliOss['domain'])[0] . '//';
                        $houhttp = explode('//', $aliOss['domain'])[1];
                        while (substr($url, 0, 1) == '/') {
                            $url = substr($url, 1);
                        }
                        if($aliOss['domainIs'] == 1 && $aliOss['domain_bind']){
                            if(strpos($aliOss['domain_bind'], "http") !== false) {
                                $url = $aliOss['domain_bind']. '/' . $url;
                            }else{
                                $url = 'https://' . $aliOss['domain_bind']. '/' . $url;
                            }
                        }else{
                            if(strpos($houhttp, $aliOss['bucket']) !== false){
                                $url = $qianhttp  . $houhttp . '/' . $url;
                            }else{
                                $url = $qianhttp . $aliOss['bucket'] . '.' . $houhttp . '/' . $url;
                            }
                        }
                    } else {
                        if ($aliOss) {
                            $url = $aliOss['folder_name'] ? $aliOss['folder_name']. '/' .ltrim($url, '/') : $url;
                            if($aliOss['domainIs'] == 1 && $aliOss['domain_bind']){
                                $url = 'https://' . $aliOss['domain_bind'] . '/' . $url;
                            }else{
                                $url = 'https://' . $aliOss['bucket'] . '.' . $aliOss['domain'] . '/' . $url;
                            }

                        }
                    }
                }
            }
            if(!empty($aliOss['imgstyle']) && strpos($url, $aliOss['imgstyle']) == false){ //阿里云图片样式
                $url = $url . '?' . $aliOss['imgstyle'];
            }
        } else {
            if ($aliOss && strpos($aliOss['domain'], "http") !== false) {
                $qianhttp = explode('//', $aliOss['domain'])[0] . '//';
                $houhttp = explode('//', $aliOss['domain'])[1];
                if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '80'){
                    $qianhttp = 'http://';
                }
                if($aliOss['folder_name']){
                    if (strpos($url, $qianhttp . $aliOss['bucket'] . '.' . $houhttp . '/' . $aliOss['folder_name'] . '/') !== false) {
                        $url = explode($qianhttp . $aliOss['bucket'] . '.' . $houhttp . '/' . $aliOss['folder_name'], $url)[1];
                    }
                }else{
                    if (strpos($url, $qianhttp . $aliOss['bucket'] . '.' . $houhttp . '/') !== false) {
                        $url = explode($qianhttp . $aliOss['bucket'] . '.' . $houhttp, $url)[1];
                    }
                }

            }
            if(!empty($aliOss['imgstyle'])){ //阿里云图片样式
                $url = str_replace('?'.$aliOss['imgstyle'], '', $url);
            }
        }
    } else if ($remote == 4) {
        if ($type == 1) {
            if (strpos($url, 'http') === false) {
                if (strpos($url, '/diypage/img/blank.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default/default_start.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default/tcgg.jpg') !== false) {
                    $url = $url;
                } else if (strpos($url, '/diypage/resource/images/diypage/default') !== false) {
                    $url = getLocalImage($url);
                }else if (strpos($url, '/diypage/template_img/') !== false) {
                    if(strpos($url, 'http' !== false)){
                        $url = $url;
                    }else{
                        $url = 'https://' . $_SERVER['HTTP_HOST'].$url;
                    }
                }  else if (strpos($url, 'diyusercenter/resource/images/') !== false) {
                    $url = 'https://' . $_SERVER['HTTP_HOST'].$url;
                }else {
                    if($cos['folder_name']){
                        if(strpos($url, $cos['folder_name']) === false){
                            $url = $cos['folder_name']. '/' .ltrim($url, '/');
                        }
                    }

                    if ($cos['domain_bind']) {
                        $url = $cos['domain_bind'] . '/' . $url;
                    } else {
                        $url = 'https://' . $cos['bucket'] . '.cos.' . $cos['region'] . '.myqcloud.com/' . $url;
                    }
                }
            }
        } else {
            if($cos['domain_bind']){
                //去掉https
                $url = str_replace('https://', '', $url);
                $url = str_replace('http://', '', $url);
                $cos['domain_bind'] = str_replace('https://', '', $cos['domain_bind']);
                $cos['domain_bind'] = str_replace('http://', '', $cos['domain_bind']);
                $url = str_replace($cos['domain_bind'] . '/', '', $url);
            }else{
                if(strpos($url, 'https') !== false){
                    $url = str_replace('https://', '', $url);
                }
                if(strpos($url, 'http') !== false){
                    $url = str_replace('http://', '', $url);
                }
                if(strpos($url, $_SERVER['HTTP_HOST']) !== false){
                    $url = str_replace($_SERVER['HTTP_HOST'], '', $url);
                }

                $url = str_replace($cos['bucket'] . '.cos.' . $cos['region'] . '.myqcloud.com/', '', $url);

            }

            if($cos['folder_name']){
                $url = str_replace($cos['folder_name'] . '/', '', $url);
            }
            $url = str_replace($_SERVER['HTTP_HOST'], '', $url);
        }
    }
    return $url;
}

function removePicStyle($url)
{
    $url = (string)$url;
    $style = 'x-oss-process=image/resize,m_fixed,w_480/quality,Q_75';
    if ($url === '' || strpos($url, 'x-oss-process=') === false) {
        return $url;
    }
    $url = str_replace(['?' . $style . '&', '&' . $style . '&'], ['?', '&'], $url);
    $url = str_replace(['?' . $style, '&' . $style], '', $url);
    $url = rtrim($url, '?&');
    return $url;
}

function normalizePublicAssetUrl($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }
    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    if (preg_match('/^http:\/\//i', $url)) {
        $url = preg_replace('/^http:\/\//i', 'https://', $url);
    }
    return rewritePublicAssetHost($url);
}

function publicAssetHostRewriteMap()
{
    $map = [];
    $raw = (string)env(
        'PUBLIC_ASSET_HOST_REWRITE_MAP',
        getenv('PUBLIC_ASSET_HOST_REWRITE_MAP') ?: ''
    );
    if ($raw === '') {
        return $map;
    }
    foreach (explode(',', str_replace(['，', ';'], ',', $raw)) as $item) {
        $item = trim($item);
        if ($item === '') {
            continue;
        }
        $delimiter = strpos($item, '=>') !== false ? '=>' : '=';
        $parts = explode($delimiter, $item, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $from = normalizePublicAssetHost($parts[0]);
        $to = normalizePublicAssetHost($parts[1]);
        if ($from !== '' && $to !== '') {
            $map[strtolower($from)] = $to;
        }
    }
    return $map;
}

function normalizePublicAssetHost($host)
{
    $host = trim((string)$host);
    if ($host === '') {
        return '';
    }
    if (strpos($host, '//') === 0) {
        $host = 'https:' . $host;
    }
    if (strpos($host, '://') === false) {
        $host = 'https://' . $host;
    }
    $parsed = parse_url($host);
    return strtolower((string)($parsed['host'] ?? ''));
}

function rewritePublicAssetHost($url)
{
    if (!preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    $parsed = parse_url($url);
    $host = strtolower((string)($parsed['host'] ?? ''));
    if ($host === '') {
        return $url;
    }
    $map = publicAssetHostRewriteMap();
    if (!isset($map[$host])) {
        return $url;
    }
    $scheme = strtolower((string)($parsed['scheme'] ?? 'https')) === 'http' ? 'https' : (string)$parsed['scheme'];
    $path = (string)($parsed['path'] ?? '');
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
    return $scheme . '://' . $map[$host] . $path . $query . $fragment;
}

function isProxyableExternalImageUrl($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        return false;
    }
    $parts = parse_url($url);
    if (empty($parts['scheme']) || empty($parts['host'])) {
        return false;
    }
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return false;
    }
    $host = strtolower($parts['host']);
    $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($currentHost && $host === $currentHost) {
        return false;
    }

    $allowedHosts = [
        'ai-jf-1307475442.cos.ap-shanghai.myqcloud.com',
        'ai.jfyuntu.com',
        'ai-test.jfyuntu.com',
    ];
    $envHosts = (string)env('AI_RESOURCE_IMAGE_PROXY_HOSTS', getenv('AI_RESOURCE_IMAGE_PROXY_HOSTS') ?: '');
    if ($envHosts !== '') {
        foreach (explode(',', str_replace(['，', ';', ' '], ',', $envHosts)) as $item) {
            $item = strtolower(trim($item));
            if ($item !== '') {
                $allowedHosts[] = $item;
            }
        }
    }
    if (in_array($host, array_values(array_unique($allowedHosts)), true)) {
        return true;
    }

    return preg_match('/^ai-jf-[a-z0-9-]+\.cos\.[a-z0-9-]+\.myqcloud\.com$/i', $host) === 1;
}

function proxyExternalImageUrl($url)
{
    $url = removePicStyle(trim((string)$url));
    if ($url === '' || !isProxyableExternalImageUrl($url)) {
        return $url;
    }
    $root = defined('ROOT_HOST') ? ROOT_HOST : '';
    if (!$root && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $root = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
    if (!$root) {
        return $url;
    }
    return rtrim($root, '/') . '/api/common/image_proxy?url=' . rawurlencode($url);
}

function getResourceImageProxyToken($picId, $type = 'thumb')
{
    $picId = (int)$picId;
    $type = in_array($type, ['thumb', 'preview', 'original'], true) ? $type : 'thumb';
    $secret = (string)env(
        'AI_RESOURCE_BRIDGE_TOKEN',
        getenv('AI_RESOURCE_BRIDGE_TOKEN') ?: (getenv('JIAFANGYUN_BRIDGE_TOKEN') ?: 'jiafangyun-resource-image')
    );
    return substr(hash_hmac('sha256', $picId . '|' . $type, $secret), 0, 24);
}

function isValidResourceImageProxyToken($picId, $type, $token)
{
    $expected = getResourceImageProxyToken($picId, $type);
    return is_string($token) && hash_equals($expected, (string)$token);
}

function buildResourceImageProxyUrl($picId, $type = 'thumb')
{
    $picId = (int)$picId;
    if ($picId <= 0) {
        return '';
    }
    $type = in_array($type, ['thumb', 'preview', 'original'], true) ? $type : 'thumb';
    $root = defined('ROOT_HOST') ? ROOT_HOST : '';
    if (!$root && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $root = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
    if (!$root) {
        return '';
    }
    return rtrim($root, '/') . '/api/common/resource_image?pic_id=' . $picId
        . '&type=' . rawurlencode($type)
        . '&token=' . getResourceImageProxyToken($picId, $type);
}

function getApiRootUrl()
{
    $root = defined('ROOT_HOST') ? ROOT_HOST : '';
    if (!$root && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $root = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }
    return rtrim($root, '/');
}

function getJiafangyunPcBaseUrl()
{
    $base = trim((string)env('JIAFANGYUN_PC_BASE_URL', getenv('JIAFANGYUN_PC_BASE_URL') ?: ''));
    if ($base === '') {
        $base = 'https://pic.jfyuntu.com/';
    }
    return rtrim($base, '/') . '/';
}

function buildPictureDownloadRequestUrl($picId)
{
    $picId = (int)$picId;
    if ($picId <= 0) {
        return '';
    }
    $root = getApiRootUrl();
    $path = '/api/user/download/original?pic_id=' . $picId;
    return $root ? ($root . $path) : $path;
}

function buildPictureImageUrls($pictureOrUrl, $previewUrl = '')
{
    $pic = is_object($pictureOrUrl) ? $pictureOrUrl : null;
    $displayUrl = '';
    $originalUrl = '';

    if ($pic) {
        $picId = (int)($pic->id ?? 0);
        $isImported = method_exists($pic, 'isImportedResourcePicture') && $pic->isImportedResourcePicture();
        if ($isImported && $picId > 0) {
            return [
                'thumb' => buildResourceImageProxyUrl($picId, 'thumb'),
                'preview' => buildResourceImageProxyUrl($picId, 'preview'),
                'edit' => buildResourceImageProxyUrl($picId, 'preview'),
                'origin' => '',
                'download' => buildPictureDownloadRequestUrl($picId),
            ];
        }
        $displayUrl = (string)($pic->TruePic ?? '');
    } else {
        $displayUrl = trim((string)$pictureOrUrl);
    }

    if ($previewUrl !== '') {
        $displayUrl = trim((string)$previewUrl);
    }
    $displayUrl = normalizePublicAssetUrl($displayUrl);
    $originalUrl = normalizePublicAssetUrl(removePicStyle($displayUrl));

    return [
        'thumb' => $displayUrl,
        'preview' => $displayUrl,
        'edit' => $displayUrl,
        'origin' => $originalUrl,
        'download' => $pic ? buildPictureDownloadRequestUrl((int)($pic->id ?? 0)) : $originalUrl,
    ];
}

/**获取本地图片全路径
 * @param $image_url
 * @return mixed|string
 */
function getLocalImage($image_url)
{
    $image_url = $image_url ? $image_url : '/image/static/pay_list_person.png';
    if(strpos($image_url, $_SERVER['HTTP_HOST']) !== false){
        return $image_url;
    }
    return  'https://' . $_SERVER['HTTP_HOST'] . STATIC_ROOT. $image_url;
}

/**设置本地图片去掉域名路径
 * @param $image_url
 * @return mixed|void
 */
function setLocalImage($image_url)
{
    $image_url = str_replace('https://', '', $image_url);
    $image_url = str_replace('http://', '', $image_url);
    if(strpos($image_url, $_SERVER['HTTP_HOST']) !== false){
        return explode($_SERVER['HTTP_HOST'], $image_url)[1];
    }
    return $image_url;
}

function GetRedisConf()
{
    return [
        // 服务器地址
        'host'            => env('REDIS.HOSTNAME', '127.0.0.1'),
        // 端口
        'port'        => env('REDIS.HOSTPORT', '6379'),
        // 连接密码
        'password'        => env('REDIS.PASSWORD', ''),
        // 选择库
        'select'        => env('REDIS.SELECT', '0'),
    ];
}


/**远程附件设置存入缓存
 * @param $uniacid
 * @return array|mixed
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function cacheRemoteSet($uniacid, $cache_set=false)
{
    $redis = new Redis(GetRedisConf());
    $remote_config = $redis->get('remote_set_config_'.$uniacid);
    if(!$remote_config || $cache_set){
        if($uniacid){
            $remote_info = Db::name("wd_xcx_base")->where("uniacid", $uniacid)->field("remote, use_remote")->find();  //当前项目设置
            if (!$remote_info) {
                $use_remote = 2;
                $remote = 1;
            } else {
                $use_remote = $remote_info['use_remote'];
                $remote = $remote_info['remote'];
            }
        }else{
            $use_remote = 1;
        }
        $qiniu = null;
        $aliOss = null;
        $cos = null;
        if ($use_remote == 1) {   //系统设置
            $global_remote = Db::name('wd_xcx_com_about')->where('id', 1)->field('globalremote')->find();
            if (!$global_remote) {
                $remote = 1;
            } else {
                $remote = $global_remote['globalremote'];
            }
            $qiniu = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 2)->find();
            $aliOss = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 3)->find();
            $cos = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 4)->find();
        } else {
            if($remote == 1){
                //检查服务器赠送空间为平台服务器还是平台远程附件
                $give_remote_type = Db::name('wd_xcx_base')->where('uniacid', $uniacid)->value('give_remote_type');
                if($give_remote_type == 2){
                    $global_remote = Db::name('wd_xcx_com_about')->where('id', 1)->field('globalremote')->find();
                    if (!$global_remote) {
                        $remote = 1;
                    } else {
                        $remote = $global_remote['globalremote'];
                    }
                    $qiniu = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 2)->find();
                    $aliOss = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 3)->find();
                    $cos = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 4)->find();
                }
            }else{
                $qiniu = Db::name("wd_xcx_remote")->where("uniacid", $uniacid)->where('type', 2)->find();
                $aliOss = Db::name("wd_xcx_remote")->where("uniacid", $uniacid)->where('type', 3)->find();
                $cos = Db::name("wd_xcx_remote")->where("uniacid", $uniacid)->where('type', 4)->find();
            }
        }
        $remote_config = [
            'remote' => $remote,
            'qiniu' => $qiniu,
            'aliOss' => $aliOss,
            'cos' => $cos,
        ];
        $redis->set('remote_set_config_'.$uniacid, json_encode($remote_config), 1800);
        return $remote_config;
    }
    if (is_string($remote_config)){
        return json_decode($remote_config, true);
    }else{
        return $remote_config;
    }
}

/**
 * 获取远程附件设置
 */
function getRemoteConfig($uniacid)
{
    $remote_info = Db::name("wd_xcx_base")->where("uniacid", $uniacid)->field("remote, use_remote")->find();  //当前项目设置
    if (!$remote_info) {
        $use_remote = 1;
        $remote = 1;
    } else {
        $use_remote = $remote_info['use_remote'];
        $remote = $remote_info['remote'];
    }
    $qiniu_info = [];
    $ali_info = [];
    $ten_cos = [];
    if ($use_remote == 1) {   //系统设置
        $global_remote = Db::name('wd_xcx_com_about')->where('id', 1)->field('globalremote')->find();
        if (!$global_remote) {
            $remote = 1;
        } else {
            $remote = $global_remote['globalremote'];
        }
        if ($remote == 2) {
            $qiniu_info = Db::name("wd_xcx_remote")->where("type", 2)->where("uniacid", -1)->find();
        } elseif ($remote == 3) {
            $ali_info = Db::name("wd_xcx_remote")->where("type", 3)->where("uniacid", -1)->find();
        } elseif ($remote == 4) {
            $ten_cos = Db::name("wd_xcx_remote")->where("type", 4)->where("uniacid", -1)->find();
        }
    } elseif ($use_remote == 2) {  //自己的设置
        if ($remote == 2) {
            $qiniu_info = Db::name("wd_xcx_remote")->where("type", 2)->where("uniacid", $uniacid)->find();
        } elseif ($remote == 3) {
            $ali_info = Db::name("wd_xcx_remote")->where("type", 3)->where("uniacid", $uniacid)->find();
        } elseif ($remote == 4) {
            $ten_cos = Db::name("wd_xcx_remote")->where("type", 4)->where("uniacid", $uniacid)->find();
        } else{
            //检查服务器赠送空间为平台服务器还是平台远程附件
            $give_remote_type = Db::name('wd_xcx_base')->where('uniacid', $uniacid)->value('give_remote_type');
            if($give_remote_type == 2){
                $global_remote = Db::name('wd_xcx_com_about')->where('id', 1)->field('globalremote')->find();
                if (!$global_remote) {
                    $remote = 1;
                } else {
                    $remote = $global_remote['globalremote'];
                }
                $qiniu_info = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 2)->find();
                $ali_info = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 3)->find();
                $ten_cos = Db::name("wd_xcx_remote")->where("uniacid", -1)->where('type', 4)->find();
            }
        }
    }

    return [
        'remote' => $remote,
        'qiniu' => $qiniu_info,
        'oss' => $ali_info,
        'ten_cos' => $ten_cos
    ];

}


function remove_xss($str)
{
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.TargetBlank', true);
    $obj = new HTMLPurifier($config);
    return $obj->purify($str);
}

// 文件路径：application/common.php

if (!function_exists('generate_uuid')) {
    function generate_uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

/**生成订单号
 * @param $prefix
 * @return string
 */
function generateOrderId($prefix = '')
{
    $uuid = str_replace('-', '', substr(generate_uuid(), -19));
    return $prefix . date('YmdHis') . strtoupper($uuid);
}

function validPhoneNumber($phoneNumber) {
    $pattern = '/^1[3-9]\d{9}$/';
    return preg_match($pattern, $phoneNumber) === 1;
}

function getRandStr($length, $has_num=0)
{
    $str = '';
    if($has_num){
        $pattern = 'abcdefghi1j2k3l4m5n6p7q8r9stuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
    }else{
        $pattern = 'abcdefghijklmn6pqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
    }
    for($i=0; $i < $length; $i++){
        $str .= substr($pattern, mt_rand(0, strlen($pattern) - 1), 1);
    }
    return $str;
}

function getWeek($day)
{
    $week_day = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
    return $week_day[$day];
}


function getWeekInt($day)
{
    $week_arr = [7,1,2,3,4,5,6];
    return $week_arr[$day];
}

function replacePrivacy($str, $replace_str = '****', $start=3, $length=4)
{
    return substr_replace($str, $replace_str, $start, $length);
}

// 过滤掉emoji表情
function filterEmoji($str)
{
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}
