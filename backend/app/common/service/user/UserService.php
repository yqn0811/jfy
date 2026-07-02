<?php

namespace app\common\service\user;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxAlbumVisitRecord;
use app\common\model\album\WdXcxVisitFolderPwd;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\distribution\WdXcxDistributionBase;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxUserAlbumPic;
use app\common\model\user\WdXcxUserBalanceRecord;
use app\common\model\user\WdXcxUserCollectPics;
use app\common\model\user\WdXcxUserCollectUsers;
use app\common\model\user\WdXcxUserCollectAlbums;
use app\common\model\user\WdXcxUserDiamondRecord;
use app\common\model\user\WdXcxUserGiveBalanceRecord;
use app\common\model\user\WdXcxUserIntegralRecord;
use app\common\model\user\WdXcxUserPlayRecord;
use app\common\model\user\WdXcxUserVisitPicsRecord;
use app\common\model\user\WdXcxUserVisitRecord;
use app\common\model\user\WdXcxVipgrade;
use app\common\service\BaseService;
use app\common\service\coupon\UserCouponService;
use app\common\service\distribution\DistributionService;
use app\common\service\JwtService;
use app\common\service\RemoteObjectService;
use app\common\service\WxService;
use app\common\service\album\AlbumService;
use app\index\model\WdXcxPic;
use cores\utils\Utils;
use think\App;
use think\cache\driver\Redis;
use think\facade\Db;
use think\facade\Log;

class UserService extends BaseService
{
    private $userModel;
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->userModel = new WdXcxUser();
    }

    public function getHomePageInfo($targetUserId, $visitorUid = 0)
    {
        $user = WdXcxUser::find($targetUserId);
        if (!$user) {
            throwError('用户不存在');
        }
        $visitorUidInt = (int)$visitorUid;
        $targetUserIdInt = (int)$targetUserId;
        if ($user->is_show_home == 0 && $visitorUidInt !== $targetUserIdInt) {
            throwError('该用户未公开主页');
        }

        // 记录访问
        if (!$visitorUid) {
            try {
                $visitorUid = request()->userID();
            } catch (\Exception $e) {
                // Ignore error
            }
        }

        try {
            if ($visitorUid && $visitorUid != $targetUserId) {
                // Check if recorded today to avoid spam? Or just record every visit?
                // Design says "Visitor Count", usually unique or daily unique.
                // Let's record every visit for "View Count" and filter for "Visitor Count".
                // Or just record one per day per user?
                // Let's stick to simple record for now.
                WdXcxUserVisitRecord::create([
                    'uid' => $visitorUid,
                    'target_uid' => $targetUserId,
                    'create_time' => time()
                ]);
            }
        } catch (\Exception $e) {
            // Ignore error
        }

        $is_followed = false;
        if ($visitorUid && $visitorUid != $targetUserId) {
            $follow = WdXcxUserCollectUsers::where('uid', $visitorUid)
                ->where('target_uid', $targetUserId)
                ->find();
            if ($follow) {
                $is_followed = true;
            }
        }

        $is_visiting_others = ($visitorUid && $visitorUid != $targetUserId);
        $is_owner = ($visitorUid == $targetUserId && $visitorUid != 0);
        $shared_ids = [];
        if (!$is_owner && $visitorUid) {
            try {
                $shared_ids = \app\common\model\album\WdXcxAlbumShareBind::where('bind_uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $shared_ids = [];
            }
        }
        // 当前访问者已收藏的分类/产品 id 集合
        $collected_ids = [];
        if ($visitorUid) {
            try {
                $collected_ids = WdXcxUserCollectAlbums::where('uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $collected_ids = [];
            }
        }
        // 分类列表（顶级分类）
        $categories = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 1)
            ->where('pid', 0)
            ->when(!$is_owner, function($query) use ($shared_ids) {
                $query->where(function($q) use ($shared_ids){
                    $q->where('private_type', 1)
                      ->whereOr(function($q2) use ($shared_ids){
                          if (!empty($shared_ids)) {
                              $q2->where('private_type', 4)->whereIn('id', $shared_ids);
                          } else {
                              $q2->whereRaw('0');
                          }
                      });
                });
            })
            ->field('id,folder_name,folder_desc,new_thumb,sort,uid')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $targetUserId, $is_visiting_others, $shared_ids, $is_owner, $collected_ids){
                $bound_ids = \app\common\model\album\WdXcxProductCategoryBind::where('category_id', $item->id)->column('product_id');
                $direct_ids = \app\common\model\album\WdXcxAlbumFolder::where('pid', $item->id)
                    ->where('folder_type', 2)
                    ->column('id');
                $all_ids = array_unique(array_merge($bound_ids ?: [], $direct_ids ?: []));
                if (!empty($all_ids)) {
                    $countQuery = \app\common\model\album\WdXcxAlbumFolder::whereIn('id', $all_ids)
                        ->where('folder_type', 2)
                        ->where('uid', $targetUserId);
                    if ($is_visiting_others && !$is_owner) {
                        $countQuery->where(function($q) use ($shared_ids) {
                            $q->where('private_type', 1)
                              ->whereOr(function($q2) use ($shared_ids){
                                  if (!empty($shared_ids)) {
                                      $q2->where('private_type', 4)->whereIn('id', $shared_ids);
                                  } else {
                                      $q2->whereRaw('0');
                                  }
                              });
                        });
                    }
                    $item->product_count = $countQuery->count();
                } else {
                    $item->product_count = 0;
                }
                if($item->uid != $visitorUid){
                    $item->folder_name = $item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
            });
        // 产品列表（该用户所有产品）
        $products = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 2)
            ->when(!$is_owner, function($query) use ($shared_ids) {
                $query->where(function($q) use ($shared_ids){
                    $q->where('private_type', 1)
                      ->whereOr(function($q2) use ($shared_ids){
                          if (!empty($shared_ids)) {
                              $q2->where('private_type', 4)->whereIn('id', $shared_ids);
                          } else {
                              $q2->whereRaw('0');
                          }
                      });
                });
            })
            ->field('id,folder_name,folder_desc,new_thumb,pid,sort,uid')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $collected_ids){
                $item->son_count = $item->SonCount;
                if($item->uid != $visitorUid){
                    $item->folder_name = '@'.$item->UserInfo['nickname'].$item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
            });

        return [
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => (int)$user->gender,
            'company_name' => $user->company_name,
            'company_logo' => $user->company_logo,
            'company_desc' => $user->company_desc,
            'contact_mobile' => $user->contact_mobile,
            'contact_wechat' => $user->contact_wechat,
            'is_show_home' =>$user->is_show_home,
            'address_province' => $user->address_province,
            'address_city' => $user->address_city,
            'address_district' => $user->address_district,
            'address_detail' => $user->address_detail,
            'visit_no_need_nickname' => (int)$user->visit_no_need_nickname,
            'visit_no_need_mobile' => (int)$user->visit_no_need_mobile,
            'visit_allow_save_pic' => (int)$user->visit_allow_save_pic,
            'home_watermark_text' => $user->home_watermark_text,
            'home_service_name' => $user->home_service_name,
            'home_share_title' => $user->home_share_title,
            'home_share_desc' => $user->home_share_desc,
            'home_share_image' => $user->home_share_image,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'industry_info' => (int)$user->industry_info,
            'is_collect' => $is_followed,
            'categories' => $categories,
            'products' => $products,
        ];
    }

    public function getHomeCategories($targetUserId, $visitorUid = 0)
    {
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }
        $is_owner = ($visitorUid == $targetUserId && $visitorUid != 0);
        $shared_ids = [];
        if (!$is_owner && $visitorUid) {
            try {
                $shared_ids = \app\common\model\album\WdXcxAlbumShareBind::where('bind_uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $shared_ids = [];
            }
        }
        $collected_ids = [];
        if ($visitorUid) {
            try {
                $collected_ids = WdXcxUserCollectAlbums::where('uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $collected_ids = [];
            }
        }
        $categories = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 1)
            ->where('pid', 0)
            ->when(!$is_owner, function($query) use ($shared_ids) {
                $query->where(function($q) use ($shared_ids){
                    $q->where('private_type', 1)
                      ->whereOr(function($q2) use ($shared_ids){
                          if (!empty($shared_ids)) {
                              $q2->where('private_type', 4)->whereIn('id', $shared_ids);
                          } else {
                              $q2->whereRaw('0');
                          }
                      });
                });
            })
            ->field('id,folder_name,folder_desc,new_thumb,sort,uid')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $targetUserId, $is_owner, $shared_ids, $collected_ids){
                $bound_ids = \app\common\model\album\WdXcxProductCategoryBind::where('category_id', $item->id)->column('product_id');
                $direct_ids = \app\common\model\album\WdXcxAlbumFolder::where('pid', $item->id)->where('folder_type', 2)->column('id');
                $all_ids = array_unique(array_merge($bound_ids ?: [], $direct_ids ?: []));
                if (!empty($all_ids)) {
                    $countQuery = \app\common\model\album\WdXcxAlbumFolder::whereIn('id', $all_ids)->where('folder_type', 2)->where('uid', $targetUserId);
                    if (!$is_owner) {
                        $countQuery->where(function($q) use ($shared_ids) {
                            $q->where('private_type', 1)
                              ->whereOr(function($q2) use ($shared_ids){
                                  if (!empty($shared_ids)) {
                                      $q2->where('private_type', 4)->whereIn('id', $shared_ids);
                                  } else {
                                      $q2->whereRaw('0');
                                  }
                              });
                        });
                    }
                    $item->product_count = $countQuery->count();
                } else {
                    $item->product_count = 0;
                }
                if($item->uid != $visitorUid){
                    $item->folder_name = $item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
            });
        return $categories;
    }

    public function getHomeProducts($targetUserId, $visitorUid = 0, $cateId = 0)
    {
        AlbumService::ensureProductStatusColumns();
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }
        $is_owner = ($visitorUid == $targetUserId && $visitorUid != 0);
        $shared_ids = [];
        if (!$is_owner && $visitorUid) {
            try {
                $shared_ids = \app\common\model\album\WdXcxAlbumShareBind::where('bind_uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $shared_ids = [];
            }
        }
        $collected_ids = [];
        if ($visitorUid) {
            try {
                $collected_ids = WdXcxUserCollectAlbums::where('uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $collected_ids = [];
            }
        }
        $query = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 2);

        if ($cateId) {
            $bound_ids = \app\common\model\album\WdXcxProductCategoryBind::where('category_id', $cateId)->column('product_id');
            $direct_ids = \app\common\model\album\WdXcxAlbumFolder::where('pid', $cateId)
                ->where('folder_type', 2)
                ->column('id');
            $all_ids = array_unique(array_merge($bound_ids ?: [], $direct_ids ?: []));
            if (empty($all_ids)) {
                return [];
            }
            $query = $query->whereIn('id', $all_ids);
        }

        $products = $query
            ->when(!$is_owner, function($query) use ($shared_ids) {
                $query->where(function($q) use ($shared_ids){
                    $q->where('private_type', 1)
                      ->whereOr(function($q2) use ($shared_ids){
                          if (!empty($shared_ids)) {
                              $q2->where('private_type', 4)->whereIn('id', $shared_ids);
                          } else {
                              $q2->whereRaw('0');
                          }
                      });
                });
            })
            ->field('id,folder_name,folder_desc,new_thumb,pid,sort,uid,is_hot')
            ->order('is_hot desc, sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $collected_ids){
                $item->son_count = $item->SonCount;
                if($item->uid != $visitorUid){
                    $item->folder_name = $item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
            });
        return $products;
    }

    public function getHomeProductsDetails($targetUserId, $productId, $visitorUid = 0)
    {
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }

        $product = WdXcxAlbumFolder::where('id', $productId)
            ->where('folder_type', 2)
            ->where('uid', $targetUserId)
            ->find();
        if (!$product) {
            throwError('产品不存在');
        }

        $is_owner = ($visitorUid == $targetUserId && $visitorUid != 0);
        $shared_ids = [];
        if (!$is_owner && $visitorUid) {
            try {
                $shared_ids = \app\common\model\album\WdXcxAlbumShareBind::where('bind_uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $shared_ids = [];
            }
        }

        if (!$is_owner) {
            if ($product->private_type == 2) {
                throwError('此内容为私有，请勿访问');
            }
            if ($product->private_type == 4) {
                if (empty($visitorUid)) {
                    throwError('此内容为私有，请勿访问');
                }
                if (!in_array($product->id, $shared_ids)) {
                    throwError('此内容为私有，请勿访问');
                }
            }
        }

        $albumService = new AlbumService($this->app);
        return $albumService->getProductDetail($productId, $visitorUid);
    }

    private function safeString($val)
    {
        try {
            if (is_resource($val)) {
                return '';
            }
            if (is_array($val)) {
                $json = json_encode($val);
                return $json === false ? '' : $json;
            }
            if (is_object($val)) {
                return method_exists($val, '__toString') ? (string)$val : '';
            }
            return (string)$val;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * 获取主页分享链接信息
     * @param $targetUserId
     * @param string $path
     * @return array
     */
    public function getHomeShareLink($targetUserId, $path = '')
    {
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }
        $inviteCode = isset($user->invite_code) ? $user->invite_code : '';
        $title = ($user->company_name ?: $user->nickname) . '的主页';
        $mini_path = $path ?: 'pages/index/index';
        $scene = 'uid=' . $targetUserId;
        if ($inviteCode) {
            $scene .= '&invite_code=' . $inviteCode;
        }
        $share_link = '/' . ltrim($mini_path, '/') . '?' . $scene;
        return compact('share_link', 'mini_path', 'scene', 'title');
    }

    private function getShareDisplayMeta($user, $type = 'home', $id = 0)
    {
        $showName = ($user->company_name ?: $user->nickname) . '的主页';
        $tips = '长按识别二维码进入主页';

        if ($type === 'category') {
            $category = WdXcxAlbumFolder::find($id);
            if ($category) {
                $showName = $category->folder_name ?: '分类';
            } else {
                $showName = '分类';
            }
            $tips = '长按识别二维码进入分类';
        } elseif ($type === 'product') {
            $product = WdXcxAlbumFolder::find($id);
            if ($product) {
                $showName = $product->folder_name ?: '产品';
            } else {
                $showName = '产品';
            }
            $tips = '长按识别二维码进入产品';
        } elseif ($type === 'selection') {
            $selection = \app\common\model\album\WdXcxAlbumSelection::find($id);
            if ($selection) {
                $showName = $selection->name ?: '选款单';
            } else {
                $showName = '选款单';
            }
            $tips = '长按识别二维码查看选款单';
        }

        return [
            'show_name' => $showName,
            'tips' => $tips,
        ];
    }

    public function getHomeMiniProgramCode($targetUserId, $path = '', $type = 'home', $id = 0)
    {
        $user = WdXcxUser::find($targetUserId);
        if (!$user) {
            throwError('该用户未公开主页');
        }
        if ($type !== 'selection' && !$user->is_show_home) {
            throwError('该用户未公开主页');
        }
        $inviteCode = isset($user->invite_code) ? $user->invite_code : '';
        $file_path = public_path() . 'image/ewm';
        $file_name = 'home_share_' . $type . '_' . $targetUserId . '_' . (int)$id . ($path ? '_' . md5($path) : '') . '.jpg';
        $scene = 'uid=' . $targetUserId;
        if ($inviteCode) {
            $scene .= '&invite_code=' . $inviteCode;
        }
        // Add type and id to scene if needed
        if ($type !== 'home' && $id) {
             $scene .= '&type=' . $type . '&id=' . $id;
        }
        
        $data = [
            'scene' => $scene,
            'filename' => $file_name,
            'filepath' => $file_path,
        ];
        if ($path) {
            $data['path'] = $path;
        }
        try {
            (new WxService())->getUnlimitQrcode($data);
        } catch (\Throwable $e) {
            // Ignore WeChat error, will fallback to default if file not created
        }

        $displayMeta = $this->getShareDisplayMeta($user, $type, $id);
        $qrcode = ROOT_HOST . '/api/common/ewm?filename=' . $file_name;
        $qrcode_path = $file_path . '/' . $file_name;
        
        return [
            'qrcode' => $this->safeString($qrcode),
            'tips' => $this->safeString($displayMeta['tips'] ?? ''),
            'show_name' => $this->safeString($displayMeta['show_name'] ?? ''),
            'qrcode_path' => $this->safeString($qrcode_path)
        ];
    }

    public function getHomeSharePoster($targetUserId, $type = 'home', $id = 0, $path = '')
    {
        try {
            $user = WdXcxUser::find($targetUserId);
            if (!$user) {
                throwError('该用户未公开主页');
            }
            if ($type !== 'selection' && !$user->is_show_home) {
                throwError('该用户未公开主页');
            }

            try {
                $code = $this->getHomeMiniProgramCode($targetUserId, $path, $type, $id);
            } catch (\Throwable $e) {
                // Log error or just ignore for local test
                // Mock data for local testing when WeChat API fails
                $code = [
                    'qrcode' => ROOT_HOST . '/image/img_default.png',
                    'qrcode_path' => public_path() . 'image/img_default.png',
                    'tips' => '长按识别二维码进入主页',
                    'show_name' => ($user->company_name ?: $user->nickname) . '的主页'
                ];
            }
            
            // 生成海报
            $qrInput = isset($code['qrcode_path']) && file_exists($code['qrcode_path']) ? $code['qrcode_path'] : (isset($code['qrcode']) ? $code['qrcode'] : '');
            $share_thumb = $this->generateHomeSharePosterImage($targetUserId, $qrInput, $type, $id);
            if (!$share_thumb) {
                // Fallback image if poster generation fails
                $share_thumb = ROOT_HOST . '/api/common/static?path=image/img_default.png';
                try {
                    $base = (new DistributionService($this->app))->getBase();
                    if ($base && $base->share_thumb) {
                        $share_thumb = remote(1, $base->share_thumb, 1);
                    }
                } catch (\Throwable $e) {
                }
            }

            $displayMeta = $this->getShareDisplayMeta($user, $type, $id);
            $show_name = $displayMeta['show_name'] ?? (($user->company_name ?: $user->nickname) . '的主页');
            $avatar = $user->avatar;
            $company_logo = $user->company_logo;
            $company_desc = $user->company_desc;
            
            $result = [
                'qrcode' => $this->safeString($code['qrcode'] ?? ''),
                'tips' =>  $this->safeString($code['tips'] ?? ($displayMeta['tips'] ?? '')),
                'show_name' => $this->safeString($show_name),
                'share_thumb' => $this->safeString($share_thumb),
                'avatar' => $this->safeString($avatar),
                'company_logo' => $this->safeString($company_logo),
                'company_desc' => $this->safeString($company_desc),
            ];

            // Debug logging for types
            Log::info('getHomeSharePoster Result Types: ' . json_encode(array_map('gettype', $result)));
            
            return $result;
        } catch (\Throwable $e) {
            Log::error('getHomeSharePoster Fatal Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'qrcode' => '',
                'tips' => 'Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine(),
                'show_name' => '',
                'share_thumb' => '',
                'avatar' => '',
                'company_logo' => '',
                'company_desc' => '',
            ];
        }
    }

    public function getShortLink($param)
    {
        $path = isset($param['path']) ? trim($param['path']) : '';
        $title = isset($param['title']) ? trim($param['title']) : '';
        $isPermanent = !empty($param['is_permanent']);
        if ($path === '') {
            throwError('路径不能为空');
        }
        if (stripos($path, 'http://') === 0 || stripos($path, 'https://') === 0 || strpos($path, '#小程序://') !== false) {
            throwError('path必须是小程序页面路径，例如 pages/index/index 或 pages/index/index?uid=1');
        }
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }
        $link = (new WxService())->generateShortLink($path, $title, $isPermanent);
        return [
            'link' => $link,
            'path' => $path,
            'title' => $title,
            'is_permanent' => $isPermanent ? 1 : 0,
        ];
    }

    public function testLogin($uid)
    {
        $user = WdXcxUser::find($uid);
        if (!$user) {
            throwError('用户不存在');
        }
        $result = [
            'openid' => $user->openid,
            'user_uuid' => $user->user_uuid,
            'user_id' => $user->id,
        ];
        return ['token' => JwtService::createToken($result)];
    }

    /**根据登录code获取用户信息
     * @param $code
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoByCode($code, $inviteCode = '')
    {
        $code_result = (new WxService())->getUserOpenid($code);
        $safe_log = [
            'openid' => $code_result['openid'] ?? null,
            'unionid' => $code_result['unionid'] ?? null,
        ];
        Log::info('getUserOpenid 返回: '.json_encode($safe_log, JSON_UNESCAPED_UNICODE));
        if (empty($code_result['openid'])) {
            throwError('获取openid失败');
        }
        $user = $this->userModel->getUserByWechatIdentity(
            $code_result['openid'],
            $code_result['unionid'] ?? '',
            true,
            $inviteCode
        );
        $result = [
            'openid' => $user->openid,
        ];
        $result['user_uuid'] = $user->user_uuid;
        $result['user_id'] = $user->id;
        $result['token'] = JwtService::createToken($result);
        unset($result['leaguer_id']);
        unset($result['user_id']);
        $result['_user_id'] = $user->id;
        $result['user'] = [
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'vip_grade' => $user->gradeInfo->grade_level,
            'grade_name' => $user->gradeInfo->grade_name,
            'company_name' => $user->company_name,
            'company_logo' => $user->company_logo,
            'company_desc' => $user->company_desc,
            'contact_mobile' => $user->contact_mobile,
            'contact_wechat' => $user->contact_wechat,
            'address_province' => $user->address_province,
            'address_city' => $user->address_city,
            'address_district' => $user->address_district,
            'address_detail' => $user->address_detail,
            'is_show_home' => $user->is_show_home,
            'invite_code' => isset($user->invite_code) ? $user->invite_code : '',
            'invite_from_code' => isset($user->invite_from_code) ? $user->invite_from_code : '',
//            'integral' => $user->integral,
//            'diamond' => $user->diamond,
//            'give_balance' => $user->give_balance,
        ];
        return $result;
    }

    /**获取用户手机号码
     * @param $params
     * @return array
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPhone($params)
    {
        $user = $this->userModel->getUserByOpenid($params['openid']);
        if(!$user){
            throwError('openid对应用户不存在，请重新登录');
        }
        // if($user->mobile){
        //     throwError('当前用户已绑定手机号：'.$user->mobile);
        // }
        $mobile = (new WxService())->getUserPhone($params['code']);
        //入会绑定
        $user->mobile = $mobile;
        $user->join_time = time();
        $user->save();
        //新人礼包记录
        return [
            'mobile' => $user->mobile,
            'token' => JwtService::createToken([
                'openid' => $user->openid,
                'mobile' => $user->mobile,
                'user_id' => $user->id,
            ]),
            'user' => [
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'vip_grade' => $user->gradeInfo->grade_level,
                'grade_name' => $user->gradeInfo->grade_name,
                'company_name' => $user->company_name,
                'company_logo' => $user->company_logo,
                'company_desc' => $user->company_desc,
                'contact_mobile' => $user->contact_mobile,
                'contact_wechat' => $user->contact_wechat,
                'address_province' => $user->address_province,
                'address_city' => $user->address_city,
                'address_district' => $user->address_district,
                'address_detail' => $user->address_detail,
                'is_show_home' => $user->is_show_home,
            ]
        ];
    }

    /**获取用户展示二维码
     * @param $ticket_id
     * @return array
     */
    public function getUserQrcodeInfo($ticket_id)
    {
        $user_id = 0;
        try {
            $user_id = request()->userID();
        } catch (\Exception $e) {}
        $qrcode_id = $ticket_id && $ticket_id != -1 ? $ticket_id : ('MEMBER:' . ($user_id ?: 'ANON'));
        $qrcode = Utils::createQrcode($qrcode_id, '', true);
        $ticket = [[
            'past_due_time' => 0,
            'ticket_id' => -2,
            'ticket_name' => '会员码',
            'can_choose' => 1,
        ]];
        return [
            'qrcode' => $qrcode,
            'ticket' => $ticket,
            'user_ticket_id' => -2,
            'invalid_time' => '5分钟',
        ];
    }

    /**获取用户财产数据
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserProperty($type)
    {
        $user = $this->userModel->getUserByOpenid(request()->userOpenid());
        $balance = number_format((float)($user->money ?? 0), 2, '.', '');
        if($type == 3){
            $distribution_status = WdXcxDistributionBase::where('uniacid', $this->uniacid)->value('status');
            return [
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'mobile' => $user->mobile,
                'vip_grade' => $user->vip_grade,
                'grade_name' => $user->grade ? $user->grade->grade_name : '普通用户',
                'integral' => $user->integral,
                'diamond' => $user->diamond,
                'vip_end_time' => $user->gradeInfo->EndTimeStr,
                'balance' => $balance,
                'gamecoin' => (int)($user->gamecoin ?? 0),
                'lottery' => (int)($user->lottery ?? 0),
                'give_balance' => $user->give_balance,
                'current_commission' => $user->current_commission,
                'distribution_status' => $user->distribution_status == 1 ? ($distribution_status == 1 ? 1 : 0) : 0,
            ];
        }

        if($type == 2){
            $result['integral'] = $user->integral;
            $result['diamond'] = $user->diamond;
            $result['balance'] = $balance;
            $result['gamecoin'] = (int)($user->gamecoin ?? 0);
            $result['lottery'] = (int)($user->lottery ?? 0);
            $result['give_balance'] = $user->give_balance;
            return $result;
        }
        return [
            'balance' => $balance,
        ];
    }

    /**获取用户余额
     * @param $leaguer_id
     * @return string
     */
    public function getUserBalance($leaguer_id)
    {
        try {
            $user = $this->userModel->where('leaguer_id', $leaguer_id)->find();
        } catch (\Exception $e) {
            $user = null;
        }
        if(!$user){
            try {
                $user = $this->userModel->getUserByOpenid(request()->userOpenid());
            } catch (\Exception $e) {
                $user = null;
            }
        }
        return number_format((float)($user->money ?? 0), 2, '.', '');
    }

    /**获取用户游戏币余额
     * @param $leaguer_id
     * @return int|mixed
     */
    public function getUserGamecoin($leaguer_id)
    {
        try {
            $user = $this->userModel->where('leaguer_id', $leaguer_id)->find();
        } catch (\Exception $e) {
            $user = null;
        }
        if(!$user){
            try {
                $user = $this->userModel->getUserByOpenid(request()->userOpenid());
            } catch (\Exception $e) {
                $user = null;
            }
        }
        return (int)($user->gamecoin ?? 0);
    }

    /**获取用户彩票余额
     * @param $leaguer_id
     * @return int|mixed
     */
    public function getUserLottery($leaguer_id)
    {
        try {
            $user = $this->userModel->where('leaguer_id', $leaguer_id)->find();
        } catch (\Exception $e) {
            $user = null;
        }
        if(!$user){
            try {
                $user = $this->userModel->getUserByOpenid(request()->userOpenid());
            } catch (\Exception $e) {
                $user = null;
            }
        }
        return (int)($user->lottery ?? 0);
    }

    /**更新用户头像昵称
     * @param $param
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateUserInfo($param)
    {
        if (empty($param['openid'])) {
            throwError('openid不能为空');
        }

        if (!empty($param['upload_pwd'])) {
            //检查是否为4位整数
            if (!preg_match('/^[0-9]{4}$/', $param['upload_pwd'])) {
                throwError('上传密码格式错误');
            }
        }
        $user = $this->userModel->getUserByOpenid($param['openid']);
        if (!$user) {
            throwError('用户不存在');
        }
        

        if ($param['nickname'] !== null && $param['nickname'] !== '') {
            $user->nickname = $param['nickname'];
        }
        if ($param['avatar'] !== null && $param['avatar'] !== '') {
            $user->avatar = $param['avatar'];
        }
        
        // 兼容处理：如果是 null 或未设置，不覆盖，除非显式为空字符串
        if (isset($param['wx_ewm'])) {
            $user->wx_ewm = $param['wx_ewm'];
        }
        if (isset($param['user_desc'])) {
            $user->user_desc = $param['user_desc'];
        }
        if (isset($param['upload_pwd'])) {
            $user->upload_pwd = $param['upload_pwd'];
        }
        
        // 新增字段更新（仅当不为 null 时才更新，避免默认值覆盖已有数据）
        if ($param['company_name'] !== null) {
            $user->company_name = $param['company_name'];
        }
        if ($param['company_logo'] !== null) {
            $user->company_logo = $param['company_logo'];
        }
        if ($param['company_desc'] !== null) {
            $user->company_desc = $param['company_desc'];
        }
        if ($param['contact_mobile'] !== null) {
            $user->contact_mobile = $param['contact_mobile'];
        }
        if ($param['contact_wechat'] !== null) {
            $user->contact_wechat = $param['contact_wechat'];
        }
        if ($param['address_province'] !== null) {
            $user->address_province = $param['address_province'];
        }
        if ($param['address_city'] !== null) {
            $user->address_city = $param['address_city'];
        }
        if ($param['address_district'] !== null) {
            $user->address_district = $param['address_district'];
        }
        if ($param['address_detail'] !== null) {
            $user->address_detail = $param['address_detail'];
        }
        if ($param['is_show_home'] !== null) {
            $user->is_show_home = (int)$param['is_show_home'];
        }
        if ($param['visit_no_need_nickname'] !== null) {
            $user->visit_no_need_nickname = (int)$param['visit_no_need_nickname'];
        }
        if ($param['visit_no_need_mobile'] !== null) {
            $user->visit_no_need_mobile = (int)$param['visit_no_need_mobile'];
        }
        if ($param['visit_allow_save_pic'] !== null) {
            $user->visit_allow_save_pic = (int)$param['visit_allow_save_pic'];
        }
        if ($param['home_watermark_text'] !== null) {
            $user->home_watermark_text = $param['home_watermark_text'];
        }
        if ($param['home_service_name'] !== null) {
            $user->home_service_name = $param['home_service_name'] === '' ? '服务' : $param['home_service_name'];
        }
        if ($param['home_share_title'] !== null) {
            $user->home_share_title = $param['home_share_title'];
        }
        if ($param['home_share_desc'] !== null) {
            $user->home_share_desc = $param['home_share_desc'];
        }
        if ($param['home_share_image'] !== null) {
            $user->home_share_image = $param['home_share_image'];
        }
        if (isset($param['industry_info']) && $param['industry_info'] !== null && $param['industry_info'] !== '') {
            $industry = (int)$param['industry_info'];
            if (!in_array($industry, [1, 2, 3], true)) {
                throwError('行业信息不合法');
            }
            $user->industry_info = $industry;
        }
        if (isset($param['latitude']) && $param['latitude'] !== null) {
            $user->latitude = $param['latitude'];
        }
        if (isset($param['longitude']) && $param['longitude'] !== null) {
            $user->longitude = $param['longitude'];
        }
        // 性别更新（0未知 1男 2女），兼容字符串/数字
        if (isset($param['gender'])) {
            $gender = $param['gender'];
            if (is_numeric($gender)) {
                $user->gender = (int)$gender;
            } else {
                $g = strtolower(trim((string)$gender));
                if ($g === 'male' || $g === 'm' || $g === '男') {
                    $user->gender = 1;
                } elseif ($g === 'female' || $g === 'f' || $g === '女') {
                    $user->gender = 2;
                } else {
                    $user->gender = 0;
                }
            }
        }

        if(!$user->join_time){
            $user->join_time = time();
        }
        $user->save();
    }

    /**获取指定用户的卡券列表
     * @param $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getUserCouponLists($param)
    {
        $param['user_id'] = request()->userID();
        return (new UserCouponService($this->app))->getUserCouponLists($param);
    }

    /**获取指定用户的未使用卡券数量
     * @param $param
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponCount()
    {
        $param['user_id'] = request()->userID();
        return (new UserCouponService($this->app))->getUserCouponCount($param);
    }

    /**获取用户指定卡券核销二维码
     * @param $param
     * @return string
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCouponQrcode($param)
    {
        $param['user_id'] = request()->userID();
        return (new UserCouponService($this->app))->getUserCouponQrcode($param);
    }

    /**获取指定卡券核销规则
     * @param $param
     * @param $user_id
     * @return WdXcxUserCoupon|array|mixed|\think\Model|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponUseRule($param, $user_id)
    {
        return (new UserCouponService($this->app))->getCouponUseRule($param['cid'], $user_id);
    }

    /**获取用户代金券列表
     * @param $param
     * @param $user_id
     * @return array
     */
    public function getUserCouponVoucher($param, $user_id)
    {
        return (new UserCouponService($this->app))->getUserCouponVoucher($param['pay_price'], $user_id);
    }

    /**获取用户消费流水
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserBalanceRecord($param, $user_id)
    {
        $type = $param['type'];
        if(!in_array($type, [0, 1, 2])){
            throwError('参数错误');
        }
        $lists = WdXcxUserBalanceRecord::where('user_id', $user_id)
            ->where(function ($query)use($type){
                if($type){
                    $query->where('change_type', $type);
                }
            })->withoutField('id, user_id, order_id, other_info, update_time, delete_time, change_source')
            ->order('id desc')
            ->paginate(10);
        return $lists;
    }

    /**获取用户零钱流水
     * @param $param
     * @param $user_id
     * @return mixed
     * @throws \cores\exception\BaseException
     */
    public function getUserGiveBalanceRecord($param, $user_id)
    {
        $type = $param['type'];
        if(!in_array($type, [0, 1, 2])){
            throwError('参数错误');
        }
        $lists = WdXcxUserGiveBalanceRecord::where('user_id', $user_id)
            ->where(function ($query)use($type){
                if($type){
                    $query->where('change_type', $type);
                }
            })->withoutField('id, user_id, order_id, other_info, update_time, delete_time, change_source')
            ->order('id desc')
            ->paginate(10);
        return $lists;
    }

    /**获取用户资产流水
     * @param $param
     * @param $user_id
     * @return \think\Paginator
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DbException
     */
    public function getUserAssetRecord($param, $user_id, $user_leaguerID)
    {
        $type = $param['type'];
        $change_type = $param['change_type'];
        if(!in_array($type, [1, 2, 3, 4])){
            throwError('参数错误');
        }
        if(!in_array($change_type, [0, 1, 2])){
            throwError('参数错误');
        }
        if($type == 1){
            $lists = WdXcxUserIntegralRecord::where('user_id', $user_id)
                ->where(function ($query)use($change_type){
                    if($change_type){
                        $query->where('change_type', $change_type);
                    }
                })
                ->field('id, change_type, change_integral as change_value, new_integral as new_value, message, create_time')
                ->order('id desc')
                ->paginate(10)->each(function ($item){
                    $item->create_time_str = date('Y.m.d H:i:s', strtotime($item->create_time));
                    unset($item->create_time);
                });
        }else if($type == 2){
            $lists = WdXcxUserDiamondRecord::where('user_id', $user_id)
                ->where(function ($query)use($change_type){
                    if($change_type){
                        $query->where('change_type', $change_type);
                    }
                })
                ->field('id, change_type, change_diamond as change_value, new_diamond as new_value, message, create_time')
                ->order('id desc')
                ->paginate(10)->each(function ($item){
                    $item->create_time_str = date('Y.m.d H:i:s', strtotime($item->create_time));
                    unset($item->create_time);
                });
        }else if($type == 3){
            $lists = [
                'data' => [],
            ];
        }else{
            $lists = [
                'data' => [],
            ];
        }
        return $lists;
    }

    /**获取扫码消费结果
     * @param $user_id
     * @return mixed|null
     */
    public function getUserQrcodeScanStatus($user_id)
    {
        $redis = new Redis(GetRedisConf());
        $result = $redis->get($user_id.'_scan_show');
        if($result){
            $result = json_decode($result, true);
            $redis->delete($user_id.'_scan_show');
        }
        return $result;
    }

    /**获取用户游玩记录
     * @param $param
     * @param $user_id
     * @return mixed
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPlayRecord($param, $user_id)
    {
        $type = $param['type'];
        if(!in_array($type, [0,1,2])){
            throwError('参数错误');
        }
        $lists = WdXcxUserPlayRecord::where('user_id', $user_id)
            ->where(function ($query)use($type){
                if($type){
                    $type = $type == 1 ? 2 : 1;
                    $query->where('record_type', $type);
                }
            })->order('create_time desc')
            ->group('consume_date')
            ->field('consume_date')
            ->paginate(10)->each(function ($item)use($user_id){
                $record = WdXcxUserPlayRecord::where('user_id', $user_id)
                    ->where('consume_date', $item->consume_date)
                    ->order('create_time desc')
                    ->field('terminal_name, timestamp, code, amount, score_result, terminal_id, id, record_type, summary')
                    ->select()->each(function ($rec){
                        $rec->code_str = $rec->CodeStr;
                        $rec->record_type_str = $rec->RecordTypeStr;
                        $rec->record_time = date('H:i:s', bcdiv($rec->timestamp, 1000));
                        $rec->need_upload = $rec->NeedUpload;
                        $rec->voucher = $rec->voucher()->field('id, voucher_thumb, voucher_thumb, status, create_time')->find();
                    });
                $item->record = $record;
            });

        return $lists;
    }

    /**上传凭证
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserPlayVoucher($param, $user_id)
    {
        if(empty($param['pid']) || empty($param['voucher_thumb'])){
            throwError('参数不完整');
        }
        $play_record = WdXcxUserPlayRecord::where('user_id', $user_id)
            ->where('id', $param['pid'])
            ->find();
        if(!$play_record){
            throwError('指定记录不存在');
        }
        $voucher = $play_record->voucher;
        if($voucher){
            if($voucher->status == 1){
                throwError('已审核通过，无需重新上传');
            }
            $voucher->voucher_thumb = $param['voucher_thumb'];
            $change_info = $voucher->change_info;
            $voucher->change_info = array_merge($change_info, [
                [
                    'create_time' => time(),
                    'log' => '重新上传凭证'
                ]
            ]);
            if($voucher->status == 2){
                $voucher->status = 0;
            }
            $voucher->save();
        }else{
            $play_record->voucher()->save([
                'user_id' => $user_id,
                'status' => 0,
                'voucher_thumb' => $param['voucher_thumb'],
                'change_info' => [
                    [
                        'create_time' => time(),
                        'log' => '上传凭证'
                    ]
                ]
            ]);
        }
    }

    /**检查用户充值满足会员升级条件
     * @param $user_id
     * @param $order_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkUserRechargeVipGrade($user_id, $order_id)
    {
        $user = $this->userModel->getUserById($user_id);
        $grade = WdXcxVipgrade::where('uniacid', $this->uniacid)
            ->where('recharge_grade', 1)
            ->order('grade_level desc')
            ->select();
        foreach ($grade as $item){
            if($item->grade_level > $user->vip_grade && bccomp($user->total_recharge, $item->total_recharge, 2) == 1){
                $change_info = '充值达到'.$item->total_recharge.'元，升级'.$item->grade_name;
                list($add_time, $end_time) = $this->getUserChangeVipTime($user, $item);
                $user->changeUserVipGrade($user, $item->grade_level, $end_time, $change_info, $add_time);
                //赠送积分
                if($item->score_flag == 1 && $item->score_back > 0){
                    (new WdXcxUserIntegralRecord())->addRecord($user, [
                        'change_integral' => $item->score_back,
                        'order_id' => $order_id,
                        'message' => '达到会员等级回馈积分',
                        'change_source' => WdXcxUser::PROPERTY_CHANGE_SOURCE_GRADE_SEND,
                    ], WdXcxUserIntegralRecord::INTEGRAL_CHANGE_ADD);
                }
                break;
            }
        }
    }

    /**计算新会员等级到期时间
     * @param $user
     * @param $grade_info
     * @return array
     */
    private function getUserChangeVipTime($user, $grade_info)
    {
        $add_time = 0;
        if($grade_info->buy_day_limit == 0){
            $end_time = 0;
        }else{
            $end_time = strtotime(date('Y-m-d 23:59:59')) + $grade_info->buy_day_limit * 86400;
//            if($user->gradeInfo->end_time == 0){
//                $end_time = strtotime(date('Y-m-d 23:59:59')) + $grade_info->buy_day_limit * 86400;
//            }else{
//                $end_time = -1;
//                $add_time = $grade_info->buy_day_limit;
//            }
        }
        return [$add_time, $end_time];
    }

    /**用户余额兑换零钱
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exchangeUserBalance($param, $user_id)
    {
        if(empty($param['balance'])){
            throwError('请输入需要兑换的金额');
        }
        $balance = $param['balance'];
        if(!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $balance)){
            throwError('请输入正确的金额');
        }
        $user = $this->userModel->getUserById($user_id);
        $user_balance = str_replace(',', '', $user->UserBalance);
        if(bccomp($user_balance, $balance, 2) == -1){
            throwError('用户余额不足');
        }
        //余额流水
        $order_id = generateOrderId('YTL');
        (new WdXcxUserBalanceRecord())->addRecord($user, [
            'change_price' => $balance,
            'order_id' => $order_id,
            'message' => '余额转换零钱',
            'user_id' => request()->userID(),
            'change_source' => WdXcxUser::PROPERTY_CHANGE_USER_EXCHANGE_BALANCE
        ]);
        //零钱消费流水
        (new WdXcxUserGiveBalanceRecord())->addRecord($user, [
            'change_price' => $balance,
            'order_id' => $order_id,
            'message' => '余额转换零钱',
            'user_id' => request()->userID(),
            'change_source' => WdXcxUser::PROPERTY_CHANGE_USER_EXCHANGE_BALANCE
        ], WdXcxUserGiveBalanceRecord::BALANCE_CHANGE_ADD);
    }

    /**绑定分销商，获取奖励
     * @param $param
     * @param $user_id
     * @return mixed|null
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userBindDistribution($param)
    {
        if(empty($param['distribution_id'])){
            throwError('请输入推荐人ID');
        }
        if(empty($param['openid'])){
            throwError('请输入用户Openid');
        }
        $user = $this->userModel->where('openid', $param['openid'])->find();
        if(!$user){
            throwError('用户不存在');
        }
        if(!$user->mobile || !$user->leaguer_id){ //用户未注册
            $distribution_base = (new DistributionService(app()))->getBase();
            if($distribution_base && $distribution_base->status == 1){
                $coupon_info = $distribution_base->CouponInfoData;
                return [
                    'coupon_info' => $coupon_info,
                    'get_status' => 0, //未领取，立即授权注册后点击领取
                ];
            }else{
                return null;
            }
        }else{
            if($param['distribution_id'] == $user->id){
                throwError('不能绑定自己');
            }
            $distribution_user = $this->userModel->getUserById($param['distribution_id']);
            if($distribution_user->distribution_status != 1){
                throwError('推荐人状态已禁用');
            }
            $user_distribution = $user->getUserDistributionInfo();
            if($user_distribution){
                throwError('已绑定推荐人');
            }
            return $user->bindDistribution($distribution_user);
        }

    }

    /**
     * 检查并绑定分销关系（用于登录/注册自动绑定）
     * @param $userId
     * @param $distributionId
     * @return void
     */
    public function checkAndBindDistribution($userId, $distributionId)
    {
        if (empty($distributionId) || empty($userId)) {
            return;
        }
        if ($userId == $distributionId) {
            return;
        }

        try {
            $user = $this->userModel->find($userId);
            if (!$user) {
                return;
            }

            // 检查是否已有上级
            if ($user->getUserDistributionInfo()) {
                return;
            }

            // 检查推荐人是否有效
            $parentUser = $this->userModel->find($distributionId);
            if (!$parentUser || $parentUser->distribution_status != 1) {
                return;
            }

            // 绑定
            $user->bindDistribution($parentUser);
        } catch (\Exception $e) {
            Log::error('自动绑定分销关系失败: ' . $e->getMessage());
        }
    }

    /**获取用户所有图片列表
     * @param $user_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAllPicsLists($user_id, $param)
    {
        $months = WdXcxPic::where('uid', $user_id)
            ->where(function ($query)use($param){
                if(!empty($param['key'])){
                    $query->where('pic_beizhu', 'like', '%'.$param['key'].'%')
                    ->whereOr('pic_name', 'like', '%'.$param['key'].'%');
                }
            })
            ->distinct(true)
            ->field("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m') as upload_month")
            ->orderRaw("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m') desc")
            ->select();
        $months = json_decode(json_encode($months), true);
        $months = array_column($months, 'upload_month');

        // 分页处理月份
        $page = input('page', 1) ? input('page', 1) : 1;
        $listRows = 3; // 每页显示的月份数量
        $months = array_slice($months, ($page - 1) * $listRows, $listRows);
        $result = [];
        foreach ($months as $month) {

            // 获取该月份下的所有图片
            $pictures = WdXcxPic::where('uid', $user_id)
                ->where(function ($query)use($param){
                    if(!empty($param['key'])){
                        $query->where('pic_beizhu', 'like', '%'.$param['key'].'%')
                            ->whereOr('pic_name', 'like', '%'.$param['key'].'%');
                    }
                })
                ->where("DATE_FORMAT(FROM_UNIXTIME(create_time), '%Y-%m')", $month)
                ->order('id desc')
                ->field('id, imgurl, uniacid, file_type, pic_beizhu, create_time, pic_name')
                ->select()
                ->each(function ($item){
                    $item->isChecked = false;
                    $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
                    $item->nickname = $item->UserInfo['nickname'];
                    $item->pic_id = $item->id;
                    $item->picture_url = $item->TruePic;
                    $item->picture_url_original = removePicStyle($item->TruePic);
                });

            $result[] = [
                'collect_date' => $month, // 保持字段名一致以便前端兼容
                'pictures' => $pictures
            ];
        }
        return $result;

//        $lists = WdXcxPic::where('uid', $user_id)
//            ->order('id desc')
//            ->field('id, imgurl, uniacid, file_type, pic_beizhu, create_time')
//            ->paginate(30)->each(function ($item){
//                $item->isChecked = false;
//                $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
//                $item->nickname = $item->UserInfo['nickname'];
//                $item->pic_id = $item->id;
//                $item->picture_url = $item->TruePic;
//                $item->picture_url_original = removePicStyle($item->TruePic);
//            });
//        return $lists;
    }

    /**获取用户所有删除产品列表
     * @param $user_id
     * @param array $params
     * @return mixed
     */
    public function getUserAllDeleteProductLists($user_id, $params = [])
    {
        $limit = isset($params['limit']) ? $params['limit'] : 30;
        $key = isset($params['key']) ? $params['key'] : '';

        $query = WdXcxAlbumFolder::onlyTrashed()
            ->where('uid', $user_id);

        if ($key) {
            $query->where('folder_name', 'like', "%{$key}%");
        }

        $lists = $query->order('id desc')
            ->field('id, folder_name, new_thumb, create_time')
            ->paginate($limit)->each(function ($item){
                $item->imgurl = $item->NewThumb;
                $item->pic_name = $item->folder_name;
                $item->isChecked = false;
                $item->create_time_str = date('Y/m/d H:i', $item->getData('create_time'));
            });
        return $lists;
    }

    /**用户还原回收站产品
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userRestoreDeleteProducts($param, $user_id)
    {
        $ids = isset($param['product_ids']) && !empty($param['product_ids']) ? $param['product_ids'] : (isset($param['pic_ids']) ? $param['pic_ids'] : []);
        if(is_string($ids)){
             if (strpos($ids, '[') !== false) {
                 $ids = json_decode($ids, true);
             } else {
                 $ids = explode(',', $ids);
             }
        }
        if(empty($ids)){
            throwError('请选择要恢复的产品');
        }

        Db::startTrans();
        try{
            foreach ($ids as $id){
                $product = WdXcxAlbumFolder::onlyTrashed()->where('id', $id)->where('uid', $user_id)->find();
                if($product){
                    $product->restore();
                }
            }
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    /**用户彻底删除产品
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDestroyDeleteProducts($param, $user_id)
    {
        $ids = isset($param['product_ids']) && !empty($param['product_ids']) ? $param['product_ids'] : (isset($param['pic_ids']) ? $param['pic_ids'] : []);
        if(is_string($ids)){
             if (strpos($ids, '[') !== false) {
                 $ids = json_decode($ids, true);
             } else {
                 $ids = explode(',', $ids);
             }
        }
        if(empty($ids)){
            throwError('请选择要删除的产品');
        }

        Db::startTrans();
        try{
            foreach ($ids as $id){
                $product = WdXcxAlbumFolder::onlyTrashed()->where('id', $id)->where('uid', $user_id)->find();
                if($product){
                    $product->force()->delete();
                }
            }
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    /**获取用户收藏图片列表
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAllCollectPicsLists($uid, $param)
    {
        // 首先获取所有不同的收藏日期，按日期倒序排列
        // $dates = WdXcxUserCollectPics::where('uid', $uid)
        //     ->distinct(true)
        //     ->field('collect_date')
        //     ->order('collect_date desc')
        //     ->column('collect_date');
        // // 分页处理日期
        // $page = isset($param['page']) ? (int)$param['page'] : 1;
        // $listRows = 10; // 每页显示的日期数量
        // $total = count($dates);
        // $dates = array_slice($dates, ($page - 1) * $listRows, $listRows);

        // $result = [];
        // foreach ($dates as $date) {
        //     // 获取该日期下的所有收藏图片
        //     $pictures = WdXcxUserCollectPics::where('uid', $uid)
        //         ->where('collect_date', $date)
        //         ->order('id desc')
        //         ->field('id, pic_id, pic_beizhu')
        //         ->select()
        //         ->each(function ($item) {
        //             // 获取图片信息
        //             $picture = WdXcxPic::where('id', $item->pic_id)->find();
        //             if ($picture) {
        //                 $item->picture_url = $picture->TruePic;
        //                 $item->picture_url_original = removePicStyle($picture->TruePic);
        //                 $item->file_type = $picture->file_type;
        //             } else {
        //                 $item->picture_url = '';
        //                 $item->picture_url_original = '';
        //                 $item->file_type = 1;
        //             }
        //         });

        //     $result[] = [
        //         'collect_date' => $date,
        //         'pictures' => $pictures
        //     ];
        // }
        $pic_ids = [];
        if(!empty($param['key'])){
            $collect_ids = WdXcxUserCollectPics::where('uid', $uid)->column('pic_id');
            if(count($collect_ids)){
                $pic_ids = WdXcxPic::where('id', 'in', $collect_ids)
                    ->where('pic_name', 'like', '%'.$param['key'].'%')
                    ->column('id');
            }
        }
        $result = WdXcxUserCollectPics::where('uid', $uid)
            ->where(function ($query)use($param, $pic_ids){
                if(!empty($param['key'])){
                    $query->where('pic_id', 'in', $pic_ids)->whereOr('pic_beizhu', 'like', '%'.$param['key'].'%');
                }
            })
            ->order('id desc')
            ->field('id, pic_id, pic_beizhu')
            ->paginate(10)
            ->each(function ($item) {
                // 获取图片信息
                $picture = WdXcxPic::where('id', $item->pic_id)->find();
                if ($picture) {
                    $item->picture_url = $picture->TruePic;
                    $item->picture_url_original = removePicStyle($picture->TruePic);
                    $item->file_type = $picture->file_type;
                } else {
                    $item->picture_url = '';
                    $item->picture_url_original = '';
                    $item->file_type = 1;
                }
            });
        return $result;
    }
    
    
    public function getUserShowAllCollectPicsLists($uid, $param)
    {
        $pic_ids = [];
        if(!empty($param['key'])){
            $collect_ids = WdXcxUserCollectPics::where('uid', $uid)->column('pic_id');
            if(count($collect_ids)){
                $pic_ids = WdXcxPic::where('id', 'in', $collect_ids)
                    ->where('pic_name', 'like', '%'.$param['key'].'%')
                    ->column('id');
            }
        }

        // 首先获取所有不同的收藏日期，按日期倒序排列
        $dates = WdXcxUserCollectPics::where('uid', $uid)
            ->where(function ($query)use($param, $pic_ids){
                if(!empty($param['key'])){
                    $query->where('pic_id', 'in', $pic_ids)->whereOr('pic_beizhu', 'like', '%'.$param['key'].'%');
                }
            })
            ->distinct(true)
            ->field('collect_date')
            ->order('collect_date desc')
            ->column('collect_date');
        // 分页处理日期
        $page = isset($param['page']) ? (int)$param['page'] : 1;
        $listRows = 10; // 每页显示的日期数量
        $dates = array_slice($dates, ($page - 1) * $listRows, $listRows);

        $result = [];
        foreach ($dates as $date) {
            // 获取该日期下的所有收藏图片
            $pictures = WdXcxUserCollectPics::where('uid', $uid)
                ->where('collect_date', $date)
                ->where(function ($query)use($param, $pic_ids){
                    if(!empty($param['key'])){
                        $query->where('pic_id', 'in', $pic_ids)->whereOr('pic_beizhu', 'like', '%'.$param['key'].'%');
                    }
                })
                ->order('id desc')
                ->field('id, pic_id, pic_beizhu')
                ->select()
                ->each(function ($item) {
                    // 获取图片信息
                    $picture = WdXcxPic::where('id', $item->pic_id)->find();
                    if ($picture) {
                        $item->picture_url = $picture->TruePic;
                        $item->picture_url_original = removePicStyle($picture->TruePic);
                        $item->file_type = $picture->file_type;
                        $item->pic_name = $picture->pic_name;
                        $item->nickname = $picture->UserInfo['nickname'];
                        $item->upload_time = date('Y年m月d日 H:i', $picture->getData('create_time'));
                    } else {
                        $item->picture_url = '';
                        $item->picture_url_original = '';
                        $item->file_type = 1;
                        $item->pic_name = '图片名称未命名';
                        $item->nickname = '微信用户';
                        $item->upload_time = date('Y年m月d日 H:i');
                    }
                });
            $result = array_merge($result, json_decode(json_encode($pictures), true));
        }
        return ['pictures' => $result];
    }

    

    /**获取用户浏览记录
     * @param $uid
     * @param $param
     * @return array
     */
    public function getUserVisitRecords($uid, $param = [])
    {
        $type = isset($param['type']) ? $param['type'] : 'all';
        $key = isset($param['key']) ? $param['key'] : '';
        $page = isset($param['page']) ? (int)$param['page'] : 1;
        $limit = 20;

        $lists = [];
        $total = 0;

        // 1. Home Page Visits (User Profiles)
        if ($type == 'all' || $type == 'homepage' || $type == '主页') {
            $query = WdXcxUserVisitRecord::where('uid', $uid);
            // If key search is needed, we need to join user table
            if ($key) {
                $targetUids = WdXcxUser::where('nickname|company_name', 'like', "%{$key}%")->column('id');
                $query->whereIn('target_uid', $targetUids);
            }
            $countQuery = clone $query;
            $total += $countQuery->count();

            $records = $query->order('create_time desc')
                ->limit($page * $limit)
                ->select()
                ->each(function($item) {
                    $targetUser = WdXcxUser::find($item->target_uid);
                    $item->type = 'homepage';
                    $item->type_name = '主页';
                    $item->title = $targetUser ? ($targetUser->company_name ?: $targetUser->nickname) : '未知用户';
                    $item->image = $targetUser ? $targetUser->avatar : '';
                    $item->source = '查看主页';
                    $time = $item->getData('update_time') ?: $item->getData('create_time');
                    $item->time = (int)$time;
                    $item->time_str = Utils::timeAgo($item->time);
                    $item->target_id = $item->target_uid;
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // 2. Product Visits (Albums: folder_type = 2)
        if ($type == 'all' || $type == 'product' || $type == '产品') {
            $query = WdXcxAlbumVisitRecord::where('uid', $uid);
            if ($key) {
                $fids = WdXcxAlbumFolder::where('folder_type', 2)
                    ->where('folder_name', 'like', "%{$key}%")
                    ->column('id');
                if (!empty($fids)) {
                    $query->whereIn('fid', $fids);
                } else {
                    $query->whereRaw('0');
                }
            } else {
                $productFids = WdXcxAlbumFolder::where('folder_type', 2)->column('id');
                if (!empty($productFids)) {
                    $query->whereIn('fid', $productFids);
                } else {
                    $query->whereRaw('0');
                }
            }
            $countQuery = clone $query;
            $total += $countQuery->count();

            $records = $query->order('update_time desc')
                ->limit($page * $limit)
                ->select()
                ->each(function($item) {
                    $folder = WdXcxAlbumFolder::find($item->fid);
                    $item->type = 'product';
                    $item->type_name = '产品';
                    $item->title = $folder ? $folder->folder_name : '未知产品';
                    $item->image = $folder ? $folder->new_thumb : '';
                    $item->target_uid = $folder ? (int)$folder->uid : 0;
                    $item->source = '来自主页 ' . ($folder && $folder->UserInfo ? ($folder->UserInfo['company_name'] ?: $folder->UserInfo['nickname']) : '');
                    $item->time = is_numeric($item->update_time) ? (int)$item->update_time : strtotime($item->update_time);
                    if (!$item->time) $item->time = $item->getData('create_time');
                    $item->time_str = Utils::timeAgo($item->time);
                    $item->target_id = $item->fid;
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // 3. Category Visits (Albums: folder_type = 1)
        if ($type == 'all' || $type == 'category' || $type == '分类') {
            $query = WdXcxAlbumVisitRecord::where('uid', $uid);
            if ($key) {
                $fids = WdXcxAlbumFolder::where('folder_type', 1)
                    ->where('folder_name', 'like', "%{$key}%")
                    ->column('id');
                if (!empty($fids)) {
                    $query->whereIn('fid', $fids);
                } else {
                    $query->whereRaw('0');
                }
            } else {
                $categoryFids = WdXcxAlbumFolder::where('folder_type', 1)->column('id');
                if (!empty($categoryFids)) {
                    $query->whereIn('fid', $categoryFids);
                } else {
                    $query->whereRaw('0');
                }
            }
            $countQuery = clone $query;
            $total += $countQuery->count();

            $records = $query->order('update_time desc')
                ->limit($page * $limit)
                ->select()
                ->each(function($item) {
                    $folder = WdXcxAlbumFolder::find($item->fid);
                    $item->type = 'category';
                    $item->type_name = '分类';
                    $item->title = $folder ? $folder->folder_name : '未知分类';
                    $item->image = $folder ? $folder->new_thumb : '';
                    $item->target_uid = $folder ? (int)$folder->uid : 0;
                    $item->source = '来自主页 ' . ($folder && $folder->UserInfo ? ($folder->UserInfo['company_name'] ?: $folder->UserInfo['nickname']) : '');
                    $item->time = is_numeric($item->update_time) ? (int)$item->update_time : strtotime($item->update_time);
                    if (!$item->time) $item->time = $item->getData('create_time');

                    $item->time_str = Utils::timeAgo($item->time);
                    $item->target_id = $item->fid;
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // Sort by time desc
        usort($lists, function($a, $b) {
            return $b['time'] - $a['time'];
        });

        // Slice for pagination
        $offset = ($page - 1) * $limit;
        $result = array_slice($lists, $offset, $limit);

        return [
            'total' => $total,
            'current_page' => $page,
            'per_page' => $limit,
            'data' => $result
        ];
    }

    /**
     * Format time ago
     * @param $time
     * @return string
     */
    private function formatTimeAgo($time)
    {
        $time = (int)$time;
        $diff = time() - $time;
        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . '小时前';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . '天前';
        } else {
            return date('Y-m-d', $time);
        }
    }

    /**
     * 获取用户收藏记录（支持多种类型）
     * @param $uid
     * @param $param
     * @return array
     */
    public function getUserCollectRecords($uid, $param = [])
    {
        $type = isset($param['type']) ? $param['type'] : 'all';
        $key = isset($param['key']) ? $param['key'] : '';
        $page = isset($param['page']) ? (int)$param['page'] : 1;
        $limit = isset($param['limit']) ? (int)$param['limit'] : 20;

        $lists = [];
        $total = 0;

        // 1. Homepage Collections (Users)
        if ($type == 'all' || $type == 'homepage' || $type == '主页') {
            $query = WdXcxUserCollectUsers::where('uid', $uid);
            if ($key) {
                $targetUids = WdXcxUser::where('nickname|company_name', 'like', "%{$key}%")->column('id');
                $query->whereIn('target_uid', $targetUids);
            }
            $countQuery = clone $query;
            $total += $countQuery->count();

            $records = $query->order('create_time desc')
                ->limit($page * $limit)
                ->select()
                ->each(function($item) {
                    $targetUser = WdXcxUser::find($item->target_uid);
                    $item->type = 'homepage';
                    $item->type_name = '主页';
                    $item->title = $targetUser ? ($targetUser->company_name ?: $targetUser->nickname) : '未知用户';
                    $item->image = $targetUser ? $targetUser->avatar : '';
                    $item->source = '查看主页';
                    $item->time = $item->getData('create_time');
                    if (!$item->time) $item->time = is_numeric($item->create_time) ? $item->create_time : strtotime($item->create_time);
                    $item->time_str = Utils::timeAgo($item->time);
                    $item->target_id = $item->target_uid;
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // 2. Product Collections (Products in AlbumFolder: folder_type = 2)
        if ($type == 'all' || $type == 'product' || $type == '产品') {
            $query = WdXcxUserCollectAlbums::where('uid', $uid);
            if ($key) {
                $fids = WdXcxAlbumFolder::where('folder_type', 2)
                    ->where('folder_name', 'like', "%{$key}%")
                    ->column('id');
                $query->whereIn('fid', $fids);
            }
            $countQuery = clone $query;
            $total += $countQuery->count();

            $records = $query->order('create_time desc')
                ->limit($page * $limit)
                ->select()
                ->each(function($item) {
                    $folder = WdXcxAlbumFolder::where('id', $item->fid)->where('folder_type', 2)->find();
                    $item->type = 'product';
                    $item->type_name = '产品';
                    $item->title = $folder ? $folder->folder_name : '未知产品';
                    $item->image = $folder ? $folder->new_thumb : '';
                    $item->target_uid = $folder ? (int)$folder->uid : 0;
                    $item->source = '来自主页 ' . ($folder && $folder->UserInfo ? ($folder->UserInfo['company_name'] ?: $folder->UserInfo['nickname']) : '');
                    $item->time = $item->getData('create_time');
                    if (!$item->time) $item->time = is_numeric($item->create_time) ? $item->create_time : strtotime($item->create_time);
                    
                    $item->time_str = Utils::timeAgo($item->time);
                    $item->target_id = $item->fid;
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // 3. Category Collections (Albums)
        if ($type == 'all' || $type == 'category' || $type == '分类') {
            $query = WdXcxUserCollectAlbums::where('uid', $uid);
            if ($key) {
                $fids = WdXcxAlbumFolder::where('folder_name', 'like', "%{$key}%")->column('id');
                $query->whereIn('fid', $fids);
            }
            $countQuery = clone $query;
            $total += $countQuery->count();

            $records = $query->order('create_time desc')
                ->limit($page * $limit)
                ->select()
                ->each(function($item) {
                    $folder = WdXcxAlbumFolder::find($item->fid);
                    $item->type = 'category';
                    $item->type_name = '分类';
                    $item->title = $folder ? $folder->folder_name : '未知分类';
                    $item->image = $folder ? $folder->new_thumb : '';
                    $item->target_uid = $folder ? (int)$folder->uid : 0;
                    $item->source = '来自主页 ' . ($folder && $folder->UserInfo ? ($folder->UserInfo['company_name'] ?: $folder->UserInfo['nickname']) : '');
                    $item->time = $item->getData('create_time');
                    if (!$item->time) $item->time = is_numeric($item->create_time) ? $item->create_time : strtotime($item->create_time);
                    
                    $item->time_str = Utils::timeAgo($item->time);
                    $item->target_id = $item->fid;
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // Sort by time desc
        usort($lists, function($a, $b) {
            return $b['time'] - $a['time'];
        });

        // Slice for pagination
        $offset = ($page - 1) * $limit;
        $result = array_slice($lists, $offset, $limit);

        return [
            'total' => $total,
            'current_page' => $page,
            'per_page' => $limit,
            'data' => $result
        ];
    }

    /**
     * 添加收藏（统一接口）
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function addUserCollect($param, $uid)
    {
        $type = isset($param['type']) ? $param['type'] : '';
        $id = isset($param['id']) ? $param['id'] : 0;

        if (empty($type) || empty($id)) {
            throwError('参数错误');
        }

        if ($type == 'homepage' || $type == '主页') {
            $targetUser = WdXcxUser::find($id);
            if (!$targetUser) {
                throwError('用户不存在');
            }
            if ($targetUser->id == $uid) {
                throwError('不能收藏自己的主页');
            }

            $exists = WdXcxUserCollectUsers::where('uid', $uid)->where('target_uid', $id)->find();
            if (!$exists) {
                $trashed = WdXcxUserCollectUsers::onlyTrashed()->where('uid', $uid)->where('target_uid', $id)->find();
                if ($trashed) {
                    $trashed->restore();
                } else {
                    WdXcxUserCollectUsers::create([
                        'uid' => $uid,
                        'target_uid' => $id,
                        'collect_date' => date('Y-m-d')
                    ]);
                }
            }
        } elseif ($type == 'product' || $type == '产品') {
            $folder = WdXcxAlbumFolder::where('id', $id)->where('folder_type', 2)->find();
            if (!$folder) {
                throwError('产品不存在');
            }
            if ($folder->uid == $uid) {
                throwError('不能收藏自己的产品');
            }

            $exists = WdXcxUserCollectAlbums::where('uid', $uid)->where('fid', $id)->find();
            if (!$exists) {
                $trashed = WdXcxUserCollectAlbums::onlyTrashed()->where('uid', $uid)->where('fid', $id)->find();
                if ($trashed) {
                    $trashed->restore();
                } else {
                    WdXcxUserCollectAlbums::create([
                        'uid' => $uid,
                        'fid' => $id,
                        'collect_date' => date('Y-m-d')
                    ]);
                }
            }
        } elseif ($type == 'category' || $type == '分类') {
            $folder = WdXcxAlbumFolder::where('id', $id)->where('folder_type', 1)->find();
            if (!$folder) {
                throwError('分类不存在');
            }
            if ($folder->uid == $uid) {
                throwError('不能收藏自己的分类');
            }

            $exists = WdXcxUserCollectAlbums::where('uid', $uid)->where('fid', $id)->find();
            if (!$exists) {
                $trashed = WdXcxUserCollectAlbums::onlyTrashed()->where('uid', $uid)->where('fid', $id)->find();
                if ($trashed) {
                    $trashed->restore();
                } else {
                    WdXcxUserCollectAlbums::create([
                        'uid' => $uid,
                        'fid' => $id,
                        'collect_date' => date('Y-m-d')
                    ]);
                }
            }
        } else {
            throwError('不支持的收藏类型');
        }
    }

    /**
     * 取消收藏（统一接口）
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function cancelUserCollect($param, $uid)
    {
        $type = isset($param['type']) ? $param['type'] : '';
        $id = isset($param['id']) ? $param['id'] : 0; // Target ID

        if (empty($type) || empty($id)) {
            throwError('参数错误');
        }

        if ($type == 'homepage' || $type == '主页') {
            $collect = WdXcxUserCollectUsers::where('uid', $uid)->where('target_uid', $id)->find();
            if ($collect) {
                $collect->delete();
            }
        } elseif ($type == 'product' || $type == '产品') {
            $collect = WdXcxUserCollectAlbums::where('uid', $uid)->where('fid', $id)->find();
            if ($collect) {
                $collect->delete();
            }
        } elseif ($type == 'category' || $type == '分类') {
            $collect = WdXcxUserCollectAlbums::where('uid', $uid)->where('fid', $id)->find();
            if ($collect) {
                $collect->delete();
            }
        } else {
            throwError('不支持的收藏类型');
        }
    }

    /**删除用户收藏图片
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteCollectPics($param, $user_id)
    {
        $pic_ids = explode(',', $param['collect_ids']);
        Db::startTrans();
        try{
            foreach ($pic_ids as $pic_id){
                $pic = WdXcxUserCollectPics::where('id', $pic_id)->where('uid', $user_id)->find();
                if($pic){
                    $pic->delete();
                }else{
                    throwError('图片不存在');
                }
            }
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    /**删除用户图片
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteMyPics($param, $user_id)
    {
        $pic_ids = explode(',', $param['pic_ids']);
        Db::startTrans();
        try{
            foreach ($pic_ids as $pic_id){
                $pic = WdXcxPic::where('id', $pic_id)->where('uid', $user_id)->find();
                if($pic){
                    $pic->delete();
                    //查询关联的 也删除
                    $user_pics =  WdXcxUserAlbumPic::where('pic_id', $pic_id)->select();
                    foreach ($user_pics as $user_pic){
                        $user_pic->delete();
                    }
                }else{
                    throwError('图片不存在');
                }
            }
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    /**用户访问记录
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addUserVisitRecord($param, $user_id)
    {
        $type = isset($param['type']) ? $param['type'] : '';
        $id = isset($param['id']) ? (int)$param['id'] : 0;
        if (empty($type) || empty($id)) {
            throwError('参数错误');
        }
        if ($type === 'homepage') {
            if ($id == $user_id) {
                // 允许记录访问自己的主页
            }
            $targetUser = WdXcxUser::find($id);
            if(!$targetUser){
                throwError('用户不存在');
            }
            $record = WdXcxUserVisitRecord::where([
                'uid' => $user_id,
                'target_uid' => $id,
            ])->find();
            if (!$record) {
                $record = new WdXcxUserVisitRecord();
                $record->uid = $user_id;
                $record->target_uid = $id;
            }
            $record->update_time = time();
            $record->save();
            return;
        }
        if ($type === 'product' || $type === '产品' || $type === 'category' || $type === '分类') {
            $folderType = ($type === 'product' || $type === '产品') ? 2 : 1;
            $folder = WdXcxAlbumFolder::where('id', $id)->where('folder_type', $folderType)->find();
            if(!$folder){
                if ($folderType == 2) {
                    throwError('产品不存在');
                } else {
                    throwError('分类不存在');
                }
            }
            $record = WdXcxAlbumVisitRecord::where([
                'uid' => $user_id,
                'fid' => $id,
            ])->find();
            if (!$record) {
                $record = new WdXcxAlbumVisitRecord();
                $record->uid = $user_id;
                $record->fid = $id;
            }
            $record->update_time = time();
            $record->save();
            return;
        }
        throwError('类型不支持');
    }

    /**用户删除访问记录
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userDeleteVisitRecord($param, $user_id)
    {
        $ids = explode(',', $param['visit_ids']);
        Db::startTrans();
        try{
            // 尝试从所有可能的表中删除记录
            WdXcxAlbumVisitRecord::whereIn('id', $ids)->where('uid', $user_id)->delete();
            WdXcxUserVisitRecord::whereIn('id', $ids)->where('uid', $user_id)->delete();
            // 兼容旧数据
            WdXcxUserVisitPicsRecord::whereIn('id', $ids)->where('uid', $user_id)->delete();
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    public function getVisitAlbumFolderLists($param, $uid)
    {
        $fid = 0;
        $all_fids = [];
        $folder_info = '';
        if(empty($param['fid'])){
            $all_fids = WdXcxAlbumVisitRecord::where('uid', $uid)
                ->order('update_time desc')
                ->column('fid');
            $all_fids = array_unique($all_fids);
        }else{
            $fid = $param['fid'];
            $folder_info = WdXcxAlbumFolder::where('id', $fid)->find();
            if(!$folder_info){
                throwError('文件夹不存在');
            }
        }
        $lists = WdXcxAlbumFolder::where('folder_type', 2)
            ->where(function ($query)use($param,$all_fids, $fid, $uid){
            if(!empty($param['key'])){
                $query->whereLike('folder_name', '%'.$param['key'].'%');
            }
            if($fid){
                $query->where('pid', $fid);
            }else{
                $query->where('id', 'in', $all_fids);
            }
        })
            ->field('id,folder_name,folder_type,create_time, new_thumb, share_times, visit_times,uid, set_top')
            ->order('id desc')
            ->paginate(10)->each(function ($item)use($uid){
                $item->son_count = $item->SonCount;
                if($item->uid != $uid){
                    $item->folder_name = '@'.$item->UserInfo['nickname'].$item->folder_name;
                }
                $item->level = $item->FolderLeval;
            });
        return [
            'lists' => $lists,
            'folder_info' => $folder_info,
            'option_flag' => false
        ];
    }


    public function getVisitAlbumPicLists($param, $uid)
    {
        $fid = $param['fid'];
        if(empty($fid)){
            throwError('请选择相册');
        }
        $folder = WdXcxAlbumFolder::where('id', $fid)->where('folder_type', 2)->find();
        if(!$folder){
            throwError('指定相册不存在');
        }

        if($folder->uid != $uid){
            if($folder->private_type == 2){
                throwError('此相册为私有相册，请勿访问');
            }
        }
        $need_pwd = 0;
        if($folder->uid != $uid && $folder->private_type == 3){
            //查询有没输入密码的记录
            $has = WdXcxVisitFolderPwd::where([
                'uid' => $uid,
                'folder' => $folder->id,
            ])->find();
            $need_pwd = $has ? 0 : 1;
        }
        if(!$folder->show_pwd){
            $need_pwd = 0;
        }
        $lists = [];
        if($folder->show_upload_date){
            //查询置顶的
            $set_top = WdXcxUserAlbumPic::where('folder_id', $fid)
                ->where(function ($quey)use($param){
                    if(!empty($param['key'])){
                        $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                    }
                })
                ->where('set_top', 1)
                ->order('set_top_time desc')
                ->field('id, pic_id, set_top, pic_beizhu, user_id, create_time')
                ->select()->toArray();
            $set_top_ids = [];
            if(count($set_top) > 0){
                $set_top_ids = array_column($set_top, 'id');
                foreach ($set_top as $sk => $set_item){
                    // 获取图片信息
                    $picture = WdXcxPic::where('id', $set_item['pic_id'])->find();
                    if ($picture) {
                        $set_top[$sk]['picture_url'] = $picture->TruePic;
                        $set_top[$sk]['picture_url_original'] = removePicStyle($picture->TruePic);
                        $set_top[$sk]['file_type'] = $picture->file_type;
                    } else {
                        $set_top[$sk]['picture_url'] = '';
                        $set_top[$sk]['picture_url_original'] = '';
                        $set_top[$sk]['file_type'] = 1;
                    }
                    $set_item['isChecked'] = false;
                    $user_info = WdXcxUserAlbumPic::where('id', $set_item['id'])->find();
                    $set_item['nickname'] = $user_info->UserInfo['nickname'];
                    $set_item['upload_time'] = date('Y年m月d日 H:i', $user_info->getData('create_time'));
                }
                $lists[] = [
                    'collect_date' => '置顶',
                    'pictures' => $set_top,
                    'isChecked' => false,
                ];
            }

            // 首先获取所有不同的收藏日期，按日期倒序排列
            $dates = WdXcxUserAlbumPic::where('folder_id', $fid)
                ->whereNotIn('id', $set_top_ids)
                ->distinct(true)
                ->field('upload_date')
                ->order('upload_date desc')
                ->column('upload_date');
            // 分页处理日期
            $page = isset($param['page']) ? (int)$param['page'] : 1;
            $listRows = 10; // 每页显示的日期数量
            $dates = array_slice($dates, ($page - 1) * $listRows, $listRows);
            $result = [];
            foreach ($dates as $date) {
                // 获取该日期下的所有收藏图片
                $pictures = WdXcxUserAlbumPic::where('folder_id', $fid)
                    ->where('upload_date', $date)
                    ->where(function ($quey)use($param){
                        if(!empty($param['key'])){
                            $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                        }
                    })
                    ->order('id desc')
                    ->field('id, pic_id, set_top, pic_beizhu,user_id, create_time')
                    ->select()
                    ->each(function ($item) {
                        // 获取图片信息
                        $picture = WdXcxPic::where('id', $item->pic_id)->find();
                        if ($picture) {
                            $item->picture_url = $picture->TruePic;
                            $item->picture_url_original = removePicStyle($picture->TruePic);
                            $item->file_type = $picture->file_type;
                        } else {
                            $item->picture_url = '';
                            $item->picture_url_original = '';
                            $item->file_type = 1;
                        }
                        $item->isChecked = false;
                        $item->nickname = $item->UserInfo['nickname'];
                        $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
                    });

                $lists[] = [
                    'collect_date' => $date,
                    'pictures' => $pictures,
                    'isChecked' => false,
                ];
            }
        }else{
            $lists = WdXcxUserAlbumPic::where('folder_id', $fid)
                ->where(function ($quey)use($param){
                    if(!empty($param['key'])){
                        $quey->where('pic_beizhu', 'like', '%'.$param['key'].'%');
                    }
                })
                ->order('set_top desc, set_top_time desc, id desc')
                ->field('id, pic_id, set_top, pic_beizhu,user_id, create_time')
                ->paginate(30)->each(function ($item){
                    $picture_url = '';
                    $file_type = 1;
                    if($item->picture){
                        $picture_url = $item->picture->TruePic;
                        $file_type = $item->picture->file_type;
                    }
                    $item->picture_url = $picture_url;
                    $item->picture_url_original = removePicStyle($picture_url);
                    $item->file_type = $file_type;
                    $item->isChecked = false;
                    $item->nickname = $item->UserInfo['nickname'];
                    $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
                    unset($item->picture);
                });
            $lists = $lists->toArray()['data'];
        }
        $folder->upload_field = $folder->upload_field ? $folder->upload_field : [];
        return [
            'lists' => $lists,
            'show_upload_date' => $folder->show_upload_date,
            'folder' => $folder,
            'need_pwd' => $need_pwd,
            'option_flag' => false,
        ];
    }

    /**
     * 获取访问我的用户记录（访客）
     * @param $uid
     * @param array $param
     * @return array
     */
    public function getUserVisitors($uid, $param = [])
    {
        $page = isset($param['page']) ? (int)$param['page'] : 1;
        $limit = 20;

        $query = WdXcxUserVisitRecord::where('target_uid', $uid);

        $total = $query->count();

        $lists = $query->order('update_time desc, create_time desc')
            ->page($page, $limit)
            ->select()
            ->each(function($item) {
                $visitor = WdXcxUser::find($item->uid);
                $item->visitor_name = $visitor ? ($visitor->nickname ?: $visitor->company_name) : '未知用户';
                $item->visitor_avatar = $visitor ? $visitor->avatar : '';

                $time = $item->getData('update_time') ?: $item->getData('create_time');
                $item->time = (int)$time;
                $item->time_str = Utils::timeAgo($item->time);
            });

        return [
            'total' => $total,
            'current_page' => $page,
            'per_page' => $limit,
            'data' => $lists
        ];
    }

    /**
     * Helper to draw text using best available method
     */
    private function drawText($image, $size, $angle, $x, $y, $color, $font, $text)
    {
        try {
            if (function_exists('imagettftext')) {
                return \imagettftext($image, $size, $angle, $x, $y, $color, $font, $text);
            } elseif (function_exists('imagefttext')) {
                return \imagefttext($image, $size, $angle, $x, $y, $color, $font, $text);
            } else {
                return \imagestring($image, 5, $x, $y - 15, $text, $color);
            }
        } catch (\Throwable $e) {
            // Fallback to basic string if TTF fails
            return \imagestring($image, 5, $x, $y - 15, $text, $color);
        }
    }

    /**
     * 生成主页分享海报
     * @param $targetUserId
     * @param $qrcodeUrl
     * @return string
     */
    /**
     * 生成分享海报 (通用: 主页/分类/产品)
     * @param $targetUserId
     * @param $qrcodeUrl
     * @param string $type home|category|product
     * @param int $id category_id or product_id
     * @return string
     */
    private function generateHomeSharePosterImage($targetUserId, $qrcodeUrl, $type = 'home', $id = 0)
    {
        $user = WdXcxUser::find($targetUserId);
        
        // 1. Determine Background
        // Always use standard background
        $bgFile = 'wechat_share.png';
        
        $bgPath = $this->app->getRootPath() . 'public/image/' . $bgFile;
        if (!file_exists($bgPath)) {
            return '';
        }

        $info = getimagesize($bgPath);
        $bgWidth = $info[0];
        $bgHeight = $info[1];

        // Create Canvas
        $im = \imagecreatefrompng($bgPath);

        // Fonts & Colors
        $font = $this->app->getRootPath() . 'public/assets/front/douyuzhuiguangti.ttf';
        $colorBlack = \imagecolorallocate($im, 51, 51, 51);
        $colorGray = \imagecolorallocate($im, 153, 153, 153);

        // 2. Avatar (Round)
        $avatarUrl = $user->avatar;
        $avatarDrawn = false;
        
        $avatarSize = $bgWidth * 0.08; // Reduced from 0.13
        $avatarX = $bgWidth * 0.08;
        $avatarY = $bgHeight * 0.08; // Moved up from 0.12

        if ($avatarUrl) {
            try {
                $avatarContent = file_get_contents($avatarUrl);
                if ($avatarContent) {
                    $avatarImg = \imagecreatefromstring($avatarContent);
                    if ($avatarImg) {
                        // Resize to avatarSize
                        $avatarResized = \imagecreatetruecolor($avatarSize, $avatarSize);
                        // Maintain transparency
                        \imagealphablending($avatarResized, false);
                        \imagesavealpha($avatarResized, true);
                        \imagecopyresampled($avatarResized, $avatarImg, 0, 0, 0, 0, $avatarSize, $avatarSize, \imagesx($avatarImg), \imagesy($avatarImg));

                        // Circle it
                        $avatarFinal = $this->circleImage($avatarResized);

                        // Place it
                        \imagecopy($im, $avatarFinal, $avatarX, $avatarY, 0, 0, $avatarSize, $avatarSize);
                        \imagedestroy($avatarImg);
                        \imagedestroy($avatarResized);
                        \imagedestroy($avatarFinal);
                        $avatarDrawn = true;
                    }
                }
            } catch (\Exception $e) {
            }
        }
        
        // If avatar failed or missing, draw a placeholder circle
        if (!$avatarDrawn) {
            $avatarPlaceholder = \imagecreatetruecolor($avatarSize, $avatarSize);
            $bgColor = \imagecolorallocate($avatarPlaceholder, 200, 200, 200); // Light gray
            \imagefill($avatarPlaceholder, 0, 0, $bgColor);
            $avatarFinal = $this->circleImage($avatarPlaceholder);
            \imagecopy($im, $avatarFinal, $avatarX, $avatarY, 0, 0, $avatarSize, $avatarSize);
            \imagedestroy($avatarPlaceholder);
            \imagedestroy($avatarFinal);
        }

        // 3. Name (Decoupled from avatar)
        $nickname = $user->company_name ?: $user->nickname;
        if (!$nickname) $nickname = '用户'; // Fallback name
        
        $fontSize = $bgWidth * 0.035; // Reduced from 0.04
        $textX = $avatarX + $avatarSize + 20;
        $textY = $avatarY + $avatarSize / 2 + $fontSize / 2;
        $this->drawText($im, $fontSize, 0, $textX, $textY, $colorBlack, $font, $nickname);

        // 4. Collection Title & Content (Grid or Main Image)
        $collTitle = '作品集';
        if ($type === 'home') {
            $collTitle = '';
        } elseif ($type === 'category') {
             $cat = WdXcxAlbumFolder::find($id);
             if ($cat) $collTitle = $cat->folder_name;
        } elseif ($type === 'product') {
             $prod = WdXcxAlbumFolder::find($id);
             if ($prod) $collTitle = $prod->folder_name;
        } elseif ($type === 'selection') {
            $selection = \app\common\model\album\WdXcxAlbumSelection::find($id);
            if ($selection) $collTitle = $selection->name ?: '选款单';
            else $collTitle = '选款单';
        }

        $titleFontSize = $bgWidth * 0.04; // Reduced from 0.045
        $titleX = $bgWidth * 0.08;
        // 如果要调整作品集文字和Icon的垂直位置，请修改下面的 +100
        // 加大数值会往下移，减小数值会往上移
        $titleY = $avatarY + $avatarSize + 100; // Relative to avatar
        if ($collTitle) {
            $iconPath = $this->app->getRootPath() . 'public/image/folder-open.png';
            if (file_exists($iconPath)) {
                $iconImg = @\imagecreatefrompng($iconPath);
                if ($iconImg) {
                    $iconH = $titleFontSize; 
                    $iconW = $iconH * (\imagesx($iconImg) / \imagesy($iconImg));
                    $iconY = $titleY - $titleFontSize * 0.9;
                    \imagecopyresampled($im, $iconImg, $titleX, $iconY, 0, 0, $iconW, $iconH, \imagesx($iconImg), \imagesy($iconImg));
                    \imagedestroy($iconImg);
                    $titleX += $iconW + 15;
                }
            }
            $this->drawText($im, $titleFontSize, 0, $titleX, $titleY, $colorBlack, $font, $collTitle);
        }

        // Content Area Start Y
        $contentY = $titleY + 80; // Increased to 80px

        if ($type === 'home') {
            // Home: Draw share_home.png centered and scaled to 70% width
            $homeImgPath = $this->app->getRootPath() . 'public/image/share_home.png';
            if (file_exists($homeImgPath)) {
                $homeImg = \imagecreatefrompng($homeImgPath);
                if ($homeImg) {
                    $homeW = \imagesx($homeImg);
                    $homeH = \imagesy($homeImg);
                    
                    // Crop 2px from all sides (left, right, top, bottom)
                    $cropX = 2;
                    $cropY = 2;
                    $cropW = $homeW - 4; // Total width reduction: 2px left + 2px right
                    $cropH = $homeH - 4; // Total height reduction: 2px top + 2px bottom
                    
                    if ($cropW <= 0 || $cropH <= 0) { 
                        $cropX = 0; $cropY = 0; 
                        $cropW = $homeW; $cropH = $homeH; 
                    } // Safety check

                    // Target width: 70% of canvas width
                    $targetW = $bgWidth * 0.92;
                    // Maintain aspect ratio based on cropped dimensions
                    $targetH = $targetW * ($cropH / $cropW);
                    
                    // Centered X
                    $targetX = ($bgWidth - $targetW) / 2;
                    // Y Position: below title, moved up 20px
                    $targetY = $contentY - 140;

                    \imagecopyresampled($im, $homeImg, $targetX, $targetY, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);
                    \imagedestroy($homeImg);
                }
            }
            
            // Define gridX for Home type to ensure QR code positioning works (align with other types)
            $gridX = $bgWidth * 0.08;
            
        } elseif ($type === 'product') {
            // Product: 1 column * 2 rows (Pattern Images)
            $gridX = $bgWidth * 0.08;
            $gridY = $contentY;
            // 如果要调整图片之间的间距，请修改下面的 $gap 值
            $gap = 20; // Changed back to 20
            
            // Debug: Check if product exists
            $productObj = WdXcxAlbumFolder::find($id);

            $patterns = [];
            
            // 2. Fallback: Use pic_ids from Product (WdXcxAlbumFolder) if no patterns found
            if ($productObj && !empty($productObj->pic_ids)) {
                $picIds = explode(',', $productObj->pic_ids);
                // Take first 2
                $picIds = array_slice($picIds, 0, 2);
                $patterns = \app\index\model\WdXcxPic::where('id', 'in', $picIds)->select();
            }

            // Debug logging for patterns query
            $gridW = $bgWidth - ($gridX * 2);
            // Calculate height to match Category (2x2) single image height
            // Category GridW = ($bgWidth - ($gridX * 2) - $gap) / 2
            // Category GridH = Category GridW (Square)
            $categoryGridW = ($bgWidth - ($gridX * 2) - $gap) / 2;
            $gridH = $categoryGridW; 
            
            foreach ($patterns as $index => $pic) {
                // Improved image retrieval
                $pUrl = '';
                
                // 1. Try TruePic directly (WdXcxPic object)
                try {
                    $pUrl = $pic->TruePic;
                } catch (\Throwable $e) {
                }
                
                // 2. Try picture_url attribute
                if (empty($pUrl) && !empty($pic->picture_url)) {
                    $pUrl = $pic->picture_url;
                }
                
                // 3. Fallback: Manual lookup using pic_id
                if (empty($pUrl) && $pic->pic_id) {
                     $pObj = \app\index\model\WdXcxPic::find($pic->pic_id);
                     if ($pObj) $pUrl = $pObj->TruePic;
                }
                
                if ($pUrl) {
                    try {
                        // Debug log
                        \think\facade\Log::info('Product Poster Image URL: ' . $pUrl);
                        
                        $pContent = file_get_contents($pUrl);
                        if ($pContent) {
                            $pImg = \imagecreatefromstring($pContent);
                            if ($pImg) {
                                $row = $index;
                                $col = 0;
                                $px = $gridX;
                                $py = $gridY + ($row * ($gridH + $gap));

                                $pResized = $this->resizeImageCover($pImg, $gridW, $gridH);
                                \imagecopy($im, $pResized, $px, $py, 0, 0, $gridW, $gridH);
                                \imagedestroy($pImg);
                                \imagedestroy($pResized);
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        } elseif ($type === 'selection') {
            $gridX = $bgWidth * 0.08;
            $gridY = $contentY;
            $gap = 20;
            $gridW = ($bgWidth - ($gridX * 2) - $gap) / 2;
            $gridH = $gridW;
            $selectionPictures = [];

            try {
                $selectionService = new \app\common\service\album\SelectionService($this->app);
                $selectionDetail = $selectionService->getSelectionDetail($id);
                $groupedPictures = $selectionDetail['grouped_pictures'] ?? [];
                $selectionPictures = array_merge(
                    $groupedPictures['main_pictures'] ?? [],
                    $groupedPictures['variant_pictures'] ?? [],
                    $groupedPictures['detail_pictures'] ?? []
                );
            } catch (\Throwable $e) {
                \think\facade\Log::info('Selection poster fallback: ' . $e->getMessage());
            }

            $selectionPictures = array_slice($selectionPictures, 0, 4);
            foreach ($selectionPictures as $index => $picture) {
                $pUrl = $picture['src'] ?? $picture['imgurl'] ?? '';
                if (!$pUrl) {
                    continue;
                }

                try {
                    $pContent = file_get_contents($pUrl);
                    if ($pContent) {
                        $pImg = \imagecreatefromstring($pContent);
                        if ($pImg) {
                            $row = floor($index / 2);
                            $col = $index % 2;
                            $px = $gridX + ($col * ($gridW + $gap));
                            $py = $gridY + ($row * ($gridH + $gap));

                            $pResized = $this->resizeImageCover($pImg, $gridW, $gridH);
                            \imagecopy($im, $pResized, $px, $py, 0, 0, $gridW, $gridH);
                            \imagedestroy($pImg);
                            \imagedestroy($pResized);
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        } else {
            // Category (2x2 Grid)
            $gridX = $bgWidth * 0.08;
            $gridY = $contentY;
            // 如果要调整图片之间的间距，请修改下面的 $gap 值
            $gap = 20; // Changed back to 20

            $query = WdXcxAlbumFolder::where('folder_type', 2)
                ->where('private_type', 1)
                ->where('pid', $id) // Filter by Category ID
                ->order('sort desc, id desc')
                ->limit(4);
            
            if ($type === 'category') {
                $query->where('pid', $id);
            } else {
                // Fallback for unexpected types, though 'home' is handled above
                $query->where('uid', $targetUserId);
            }
            
            $products = $query->select();
            
            $gridW = ($bgWidth - ($gridX * 2) - $gap) / 2;
            $gridH = $gridW;

            foreach ($products as $index => $product) {
                $pUrl = $product->new_thumb;
                
                if ($pUrl) {
                    try {
                        $pContent = file_get_contents($pUrl);
                        if ($pContent) {
                            $pImg = \imagecreatefromstring($pContent);
                            if ($pImg) {
                                $row = floor($index / 2);
                                $col = $index % 2;
                                $px = $gridX + ($col * ($gridW + $gap));
                                $py = $gridY + ($row * ($gridH + $gap));

                                $pResized = $this->resizeImageCover($pImg, $gridW, $gridH);
                                \imagecopy($im, $pResized, $px, $py, 0, 0, $gridW, $gridH);
                                \imagedestroy($pImg);
                                \imagedestroy($pResized);
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        // 5. Footer (QR Code)
        if ($qrcodeUrl) {
            try {
                $qrContent = file_get_contents($qrcodeUrl);
                if ($qrContent) {
                    $qrImg = \imagecreatefromstring($qrContent);
                    if ($qrImg) {
                        $qrSize = $bgWidth * 0.2;
                        $qrX = $bgWidth - $gridX - $qrSize;
                        // Reduced -50 to -30 to move QR code down (closer to bottom edge)
                        $qrY = $bgHeight - $gridX - $qrSize - 15;

                        \imagecopyresampled($im, $qrImg, $qrX, $qrY, 0, 0, $qrSize, $qrSize, \imagesx($qrImg), \imagesy($qrImg));
                        \imagedestroy($qrImg);
                    }
                }
            } catch (\Exception $e) {}
        }

        // Save
        try {
            $saveDir = $this->app->getRootPath() . 'public/storage/poster';
            if (!is_dir($saveDir)) {
                if (!@mkdir($saveDir, 0755, true)) {
                    $error = error_get_last();
                    \think\facade\Log::error("Permission denied: Cannot create directory $saveDir. " . ($error['message'] ?? ''));
                }
            }
            
            $filename = 'poster_' . $type . '_' . $id . '_' . $targetUserId . '_' . time() . '.png';
            $filePath = $saveDir . '/' . $filename;
            
            if (!@\imagepng($im, $filePath)) {
                 $error = error_get_last();
                 \think\facade\Log::error("Failed to save poster image to $filePath. " . ($error['message'] ?? ''));
            }
            \imagedestroy($im);

            return request()->domain() . '/storage/poster/' . $filename;
        } catch (\Throwable $e) {
            \think\facade\Log::error('Save Poster Error: ' . $e->getMessage());
            if (isset($im) && is_resource($im)) \imagedestroy($im);
            return '';
        }
    }

    private function resizeImageCover($img, $w, $h) {
        $width = \imagesx($img);
        $height = \imagesy($img);
        $ratio = $width / $height;
        $targetRatio = $w / $h;

        if ($ratio > $targetRatio) {
            $newH = $height;
            $newW = $height * $targetRatio;
            $x = ($width - $newW) / 2;
            $y = 0;
        } else {
            $newW = $width;
            $newH = $width / $targetRatio;
            $x = 0;
            $y = ($height - $newH) / 2;
        }

        $newImg = \imagecreatetruecolor($w, $h);
        \imagecopyresampled($newImg, $img, 0, 0, $x, $y, $w, $h, $newW, $newH);
        return $newImg;
    }

    private function circleImage($img) {
        $w = \imagesx($img);
        $h = \imagesy($img);
        $newImg = \imagecreatetruecolor($w, $h);
        $transparent = \imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        \imagefill($newImg, 0, 0, $transparent);
        \imagecolortransparent($newImg, $transparent);

        $r = $w / 2;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $dx = $x - $r;
                $dy = $y - $r;
                if ($dx*$dx + $dy*$dy <= $r*$r) {
                    $c = \imagecolorat($img, $x, $y);
                    \imagesetpixel($newImg, $x, $y, $c);
                }
            }
        }
        return $newImg;
    }
}
