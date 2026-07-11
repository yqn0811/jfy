<?php

namespace app\common\service\user;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxProductCategoryBind;
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
use app\common\service\bridge\JiafangyunBridgeClient;
use app\index\model\WdXcxBase;
use app\index\model\WdXcxPic;
use app\index\service\TencentCOSService;
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

    private function getHomeWebBaseUrl()
    {
        return getJiafangyunPcBaseUrl();
    }

    private function buildHomeWebShareUrl($shareCode, $params = [])
    {
        $query = ['code' => (string)$shareCode];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[$key] = (string)$value;
        }
        return $this->getHomeWebBaseUrl() . 'share-home?' . http_build_query($query);
    }

    private function ensureHomeShareCode($user)
    {
        return (new WdXcxUser())->ensureHomeShareCodeForUser($user);
    }

    private function ensureInviteCode($user)
    {
        return (new WdXcxUser())->ensureInviteCodeForUser($user);
    }

    public function resolveHomeTargetUserId($targetUserId = 0, $shareCode = '', $allowInviteCodeFallback = false)
    {
        $shareCode = trim((string)$shareCode);
        if ($shareCode !== '') {
            (new WdXcxUser())->ensureHomePreferenceColumns();
            $user = WdXcxUser::where('home_share_code', $shareCode)->find();
            if (!$user && $allowInviteCodeFallback) {
                $user = WdXcxUser::where('invite_code', $shareCode)->find();
            }
            if (!$user) {
                throwError('分享链接无效');
            }
            return (int)$user->id;
        }

        $targetUserId = (int)$targetUserId;
        if ($targetUserId <= 0) {
            throwError('参数错误');
        }
        return $targetUserId;
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
        $this->assertHomeVisitRequirement($user, $visitorUidInt, null, true);

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
            ->field('id,folder_name,folder_desc,new_thumb,sort,uid,layout_type,pic_layout')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $targetUserId, $is_owner, $shared_ids, $collected_ids){
                $item->product_count = $this->getVisibleCategoryProductCount($item->id, $targetUserId, $is_owner, $shared_ids);
                $item->child_count = $this->getVisibleCategoryChildCount($item->id, $targetUserId, $is_owner, $shared_ids);
                $item->son_count = $item->child_count;
                $item->children = $this->getVisibleCategoryChildren($item->id, $targetUserId, $visitorUid, $is_owner, $shared_ids, $collected_ids);
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
            ->field('id,folder_name,folder_desc,new_thumb,pid,sort,uid,layout_type,pic_layout,pic_ids,detail_pic_ids')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $collected_ids){
                $this->hydrateProductThumb($item);
                $item->son_count = $item->SonCount;
                if($item->uid != $visitorUid){
                    $item->folder_name = '@'.$item->UserInfo['nickname'].$item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
                unset($item->pic_ids, $item->detail_pic_ids);
            });

        return [
            'nickname' => $user->nickname,
            'id' => (int)$user->id,
            'uid' => (int)$user->id,
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
            'share_code' => $this->ensureHomeShareCode($user),
            'invite_code' => $this->ensureInviteCode($user),
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'industry_info' => (int)$user->industry_info,
            'is_collect' => $is_followed,
            'categories' => $categories,
            'products' => $products,
        ];
    }

    private function getUserShareCodeById($uid)
    {
        $uid = (int)$uid;
        if ($uid <= 0) {
            return '';
        }
        static $codes = [];
        if (array_key_exists($uid, $codes)) {
            return $codes[$uid];
        }
        $user = WdXcxUser::find($uid);
        $codes[$uid] = $user ? $this->ensureHomeShareCode($user) : '';
        return $codes[$uid];
    }

    public function getHomeCategories($targetUserId, $visitorUid = 0, $fid = 0, $includeCurrent = 0)
    {
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }
        $is_owner = ($visitorUid == $targetUserId && $visitorUid != 0);
        $this->assertHomeVisitRequirement($user, $visitorUid, $is_owner, true);
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
            ->where('pid', (int)$fid)
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
            ->field('id,folder_name,folder_desc,new_thumb,sort,uid,layout_type,pic_layout,pid,private_type')
            ->order('sort desc, set_top desc, set_top_time desc, id desc')
            ->select()
            ->each(function($item) use ($visitorUid, $targetUserId, $is_owner, $shared_ids, $collected_ids){
                $item->product_count = $this->getVisibleCategoryProductCount($item->id, $targetUserId, $is_owner, $shared_ids);
                $item->child_count = $this->getVisibleCategoryChildCount($item->id, $targetUserId, $is_owner, $shared_ids);
                $item->son_count = $item->child_count;
                $item->children = $this->getVisibleCategoryChildren($item->id, $targetUserId, $visitorUid, $is_owner, $shared_ids, $collected_ids);
                if($item->uid != $visitorUid){
                    $item->folder_name = $item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
            });
        if ($includeCurrent && $fid) {
            $folderInfo = WdXcxAlbumFolder::where('id', (int)$fid)
                ->where('uid', $targetUserId)
                ->where('folder_type', 1)
                ->field('id,folder_name,folder_desc,new_thumb,sort,uid,layout_type,pic_layout,pid,private_type')
                ->find();
            if (!$folderInfo) {
                throwError('分类不存在');
            }
            $this->assertVisibleCategory($fid, $targetUserId, $is_owner, $shared_ids, true);
            $folderInfo->product_count = $this->getVisibleCategoryProductCount($folderInfo->id, $targetUserId, $is_owner, $shared_ids);
            $folderInfo->child_count = $this->getVisibleCategoryChildCount($folderInfo->id, $targetUserId, $is_owner, $shared_ids);
            $folderInfo->son_count = $folderInfo->child_count;
            $folderInfo->level = $folderInfo->FolderLeval;
            $folderInfo->is_collect = in_array($folderInfo->id, $collected_ids) ? 1 : 0;
            return [
                'lists' => $categories,
                'folder_info' => $folderInfo,
                'user_info' => [
                    'id' => $user->id,
                    'uid' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'company_name' => $user->company_name,
                    'company_logo' => $user->company_logo,
                    'company_desc' => $user->company_desc,
                    'contact_mobile' => $user->contact_mobile,
                    'contact_wechat' => $user->contact_wechat,
                    'industry_info' => (int)$user->industry_info,
                ],
            ];
        }
        return $categories;
    }

    private function applyVisibleCategoryScope($query, $isOwner, $sharedIds)
    {
        if ($isOwner) {
            return $query;
        }
        return $query->where(function($q) use ($sharedIds){
            $q->where('private_type', 1)
              ->whereOr(function($q2) use ($sharedIds){
                  if (!empty($sharedIds)) {
                      $q2->where('private_type', 4)->whereIn('id', $sharedIds);
                  } else {
                      $q2->whereRaw('0');
                  }
              });
        });
    }

    private function assertHomeVisitRequirement($owner, $visitorUid, $isOwner = null, $allowAnonymousPreview = false)
    {
        $visitorUid = (int)$visitorUid;
        $ownerUid = (int)$owner->id;
        if ($isOwner === null) {
            $isOwner = ($visitorUid === $ownerUid && $visitorUid !== 0);
        }
        if ($isOwner) {
            return;
        }
        if (!$visitorUid) {
            throwError('请先授权登录');
        }
    }

    private function assertVisibleCategory($categoryId, $targetUserId, $isOwner, $sharedIds, $allowDirectSharedCategory = false)
    {
        if (!$categoryId) {
            return;
        }
        $category = WdXcxAlbumFolder::where('id', (int)$categoryId)
            ->where('uid', $targetUserId)
            ->where('folder_type', 1)
            ->find();
        if (!$category) {
            throwError('分类不存在');
        }
        if ($isOwner) {
            return;
        }
        $privateType = (int)$category->private_type;
        if ($privateType === 1) {
            return;
        }
        if ($privateType === 4 && ($allowDirectSharedCategory || in_array((int)$category->id, array_map('intval', $sharedIds), true))) {
            return;
        }
        throwError('此分类未公开或仅分享可见，请通过分享链接访问');
    }

    private function getVisibleCategoryChildCount($categoryId, $targetUserId, $isOwner, $sharedIds)
    {
        $query = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 1)
            ->where('pid', $categoryId);
        return $this->applyVisibleCategoryScope($query, $isOwner, $sharedIds)->count();
    }

    private function getVisibleCategoryProductCount($categoryId, $targetUserId, $isOwner, $sharedIds)
    {
        $boundIds = WdXcxProductCategoryBind::where('category_id', (int)$categoryId)
            ->where('userid', (int)$targetUserId)
            ->column('product_id');
        $directIds = WdXcxAlbumFolder::where('uid', (int)$targetUserId)
            ->where('folder_type', 2)
            ->where('pid', (int)$categoryId)
            ->column('id');
        $productIds = array_values(array_unique(array_filter(array_map('intval', array_merge($boundIds ?: [], $directIds ?: [])))));
        if (empty($productIds)) {
            return 0;
        }

        $query = WdXcxAlbumFolder::where('uid', (int)$targetUserId)
            ->where('folder_type', 2)
            ->whereIn('id', $productIds);
        if ($isOwner) {
            return $query->count();
        }
        return $query->where(function($q) use ($sharedIds){
            $q->where('private_type', 1)
              ->whereOr(function($q2) use ($sharedIds){
                  if (!empty($sharedIds)) {
                      $q2->where('private_type', 4)->whereIn('id', $sharedIds);
                  } else {
                      $q2->whereRaw('0');
                  }
              });
        })->count();
    }

    private function getVisibleCategoryChildren($categoryId, $targetUserId, $visitorUid, $isOwner, $sharedIds, $collectedIds)
    {
        $query = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 1)
            ->where('pid', $categoryId)
            ->field('id,folder_name,folder_desc,new_thumb,sort,uid,layout_type,pic_layout,pid,private_type')
            ->order('sort desc, set_top desc, set_top_time desc, id desc');

        return $this->applyVisibleCategoryScope($query, $isOwner, $sharedIds)
            ->select()
            ->each(function($child) use ($targetUserId, $visitorUid, $isOwner, $sharedIds, $collectedIds){
                $child->product_count = $this->getVisibleCategoryProductCount($child->id, $targetUserId, $isOwner, $sharedIds);
                $child->child_count = $this->getVisibleCategoryChildCount($child->id, $targetUserId, $isOwner, $sharedIds);
                $child->son_count = $child->child_count;
                $child->children = $this->getVisibleCategoryChildren($child->id, $targetUserId, $visitorUid, $isOwner, $sharedIds, $collectedIds);
                $child->level = $child->FolderLeval;
                $child->is_collect = in_array($child->id, $collectedIds) ? 1 : 0;
                if($child->uid != $visitorUid){
                    $child->folder_name = $child->folder_name;
                }
            });
    }

    private function normalizeProductPicIds($raw)
    {
        $items = is_array($raw) ? $raw : explode(',', (string)$raw);
        $ids = [];
        foreach ($items as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function getProductUploadedPictureMap($productIds)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($ids)) {
            return [];
        }
        $rows = WdXcxUserAlbumPic::whereIn('folder_id', $ids)
            ->with(['picture'])
            ->select();
        $map = [];
        foreach ($rows as $row) {
            if (!$row->picture) {
                continue;
            }
            $folderId = (int)$row->folder_id;
            $picId = (int)$row->pic_id;
            $fileType = (int)$row->picture->file_type;
            if (!$folderId || !$picId || !in_array($fileType, [1, 2], true)) {
                continue;
            }
            if (!isset($map[$folderId])) {
                $map[$folderId] = [1 => [], 2 => []];
            }
            $map[$folderId][$fileType][$picId] = true;
        }
        return $map;
    }

    private function countProductPictures($fieldPicIds, $uploadedPictureMap, $productId, $fileType)
    {
        $ids = [];
        foreach ($this->normalizeProductPicIds($fieldPicIds) as $picId) {
            $ids[$picId] = true;
        }
        foreach (($uploadedPictureMap[(int)$productId][(int)$fileType] ?? []) as $picId => $exists) {
            if ($exists) {
                $ids[(int)$picId] = true;
            }
        }
        return count($ids);
    }

    private function hydrateProductThumb($item)
    {
        if (!$item || (string)$item->new_thumb !== '') {
            return;
        }
        $ids = array_merge(
            $this->normalizeProductPicIds($item->pic_ids ?? ''),
            $this->normalizeProductPicIds($item->detail_pic_ids ?? '')
        );
        if (empty($ids)) {
            return;
        }
        $pic = WdXcxPic::whereIn('id', array_values(array_unique($ids)))
            ->field('id,imgurl,uniacid,file_type')
            ->orderRaw('FIELD(id, ' . implode(',', array_values(array_unique($ids))) . ')')
            ->find();
        if ($pic) {
            $item->new_thumb = $pic->TruePic;
        }
    }

    public function getHomeProducts($targetUserId, $visitorUid = 0, $cateId = 0)
    {
        AlbumService::ensureProductStatusColumns();
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }
        $is_owner = ($visitorUid == $targetUserId && $visitorUid != 0);
        $this->assertHomeVisitRequirement($user, $visitorUid, $is_owner, true);
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
            $this->assertVisibleCategory($cateId, $targetUserId, $is_owner, $shared_ids, true);
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
            ->field('id,folder_name,folder_desc,new_thumb,pid,sort,uid,is_hot,layout_type,pic_layout,pic_ids,detail_pic_ids,hide_detail_pictures')
            ->order('is_hot desc, sort desc, set_top desc, set_top_time desc, id desc')
            ->select();

        $productIds = [];
        foreach ($products as $product) {
            $productIds[] = (int)$product->id;
        }
        $uploadedPictureMap = $this->getProductUploadedPictureMap($productIds);

        $products->each(function($item) use ($visitorUid, $collected_ids, $is_owner, $uploadedPictureMap){
                $this->hydrateProductThumb($item);
                $item->color_chart_count = $this->countProductPictures($item->pic_ids ?? '', $uploadedPictureMap, $item->id, 1);
                $item->detail_chart_count = ((int)($item->hide_detail_pictures ?? 0) === 1 && !$is_owner)
                    ? 0
                    : $this->countProductPictures($item->detail_pic_ids ?? '', $uploadedPictureMap, $item->id, 2);
                $item->son_count = $item->SonCount;
                if($item->uid != $visitorUid){
                    $item->folder_name = $item->folder_name;
                }
                $item->level = $item->FolderLeval;
                $item->is_collect = in_array($item->id, $collected_ids) ? 1 : 0;
                unset($item->pic_ids, $item->detail_pic_ids);
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
        $this->assertHomeVisitRequirement($user, $visitorUid, $is_owner, true);
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
        }

        $albumService = new AlbumService($this->app);
        return $albumService->getProductDetail($productId, $visitorUid);
    }

    public function getHomePictureDetail($targetUserId, $picId, $visitorUid = 0)
    {
        $targetUserId = (int)$targetUserId;
        $picId = (int)$picId;
        $visitorUid = (int)$visitorUid;
        $user = WdXcxUser::find($targetUserId);
        if (!$user || !$user->is_show_home) {
            throwError('该用户未公开主页');
        }

        $isOwner = ($visitorUid === $targetUserId && $visitorUid !== 0);
        $this->assertHomeVisitRequirement($user, $visitorUid, $isOwner, true);
        $pic = null;
        $product = null;
        $relation = null;

        $directPic = WdXcxPic::where('id', $picId)->find();
        if ($directPic) {
            $directProduct = $this->findVisibleHomeProductByPicture($targetUserId, (int)$directPic->id, $visitorUid, $isOwner);
            if ($directProduct) {
                $pic = $directPic;
                $product = $directProduct;
            }
        }

        if (!$pic || !$product) {
            $relation = WdXcxUserAlbumPic::where('id', $picId)
                ->where('user_id', $targetUserId)
                ->find();
            if (!$relation) {
                $relation = WdXcxUserAlbumPic::where('pic_id', $picId)
                    ->where('user_id', $targetUserId)
                    ->find();
            }
            if ($relation) {
                $relationPic = $relation->picture;
                if ($relationPic) {
                    $relationProduct = $this->findVisibleHomeProductByPicture($targetUserId, (int)$relationPic->id, $visitorUid, $isOwner);
                    if ($relationProduct) {
                        $pic = $relationPic;
                        $product = $relationProduct;
                    }
                }
            }
        }

        if (!$pic || !$product) {
            throwError('分享链接无效');
        }

        return $this->mapHomePictureDetail($pic, $product, $relation, $user);
    }

    private function findVisibleHomeProductByPicture($targetUserId, $picId, $visitorUid, $isOwner)
    {
        $sharedIds = [];
        if (!$isOwner && $visitorUid) {
            try {
                $sharedIds = \app\common\model\album\WdXcxAlbumShareBind::where('bind_uid', $visitorUid)->column('fid');
            } catch (\Exception $e) {
                $sharedIds = [];
            }
        }

        $picId = (int)$picId;
        $relations = WdXcxUserAlbumPic::where('pic_id', $picId)->select();
        foreach ($relations as $relation) {
            $product = WdXcxAlbumFolder::where('id', (int)$relation->folder_id)
                ->where('uid', $targetUserId)
                ->where('folder_type', 2)
                ->find();
            if ($product && $this->canVisitorSeeHomeProduct($product, $isOwner, $sharedIds)) {
                return $product;
            }
        }

        $products = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 2)
            ->whereRaw('(FIND_IN_SET(?, pic_ids) OR FIND_IN_SET(?, detail_pic_ids))', [$picId, $picId])
            ->select();
        foreach ($products as $product) {
            if ($this->canVisitorSeeHomeProduct($product, $isOwner, $sharedIds)) {
                return $product;
            }
        }

        return null;
    }

    private function canVisitorSeeHomeProduct($product, $isOwner, $sharedIds)
    {
        if ($isOwner) {
            return true;
        }
        $privateType = (int)($product->private_type ?? 1);
        if ($privateType === 1) {
            return true;
        }
        if ($privateType === 4) {
            return true;
        }
        return false;
    }

    private function mapHomePictureDetail($pic, $product = null, $relation = null, $user = null)
    {
        $imageUrls = buildPictureImageUrls($pic);
        $url = $imageUrls['preview'];
        if (!$url) {
            throwError('图片暂不可预览');
        }
        $createTime = (int)$pic->getData('create_time');
        $userInfo = $pic->UserInfo;
        $nickname = $user ? $user->nickname : ($userInfo['nickname'] ?? '');
        $picName = $pic->pic_beizhu ?: ($pic->pic_name ?: '');
        return [
            'id' => (int)$pic->id,
            'pic_id' => (int)$pic->id,
            'relation_id' => $relation ? (int)$relation->id : 0,
            'product_id' => $product ? (int)$product->id : 0,
            'folder_id' => $product ? (int)$product->id : 0,
            'uid' => (int)$pic->uid,
            'imgurl' => $url,
            'src' => $url,
            'picture_url' => $url,
            'picture_url_original' => $imageUrls['origin'] ?: removePicStyle($url),
            'thumbnail_url' => $imageUrls['thumb'],
            'preview_url' => $imageUrls['preview'],
            'file_url' => $imageUrls['origin'],
            'image_urls' => $imageUrls,
            'imageUrls' => $imageUrls,
            'pic_name' => $picName ?: '图片名称未命名',
            'pic_beizhu' => $picName,
            'file_type' => (int)$pic->file_type,
            'is_video' => (int)$pic->file_type === 2 ? 1 : 0,
            'size' => $pic->getData('size'),
            'file_size' => (int)$pic->getData('size'),
            'nickname' => $nickname,
            'avatar' => $userInfo['avatar'] ?? '',
            'upload_time' => $createTime ? date('Y年m月d日 H:i', $createTime) : '',
            'create_time' => $createTime,
        ];
    }

    private function isActiveMember($user)
    {
        if (!$user) {
            return false;
        }
        try {
            $gradeInfo = $user->VipGradeInfo;
            $gradeLevel = (int)($gradeInfo['grade_level'] ?? ($user->vip_grade ?? 0));
            $endTime = $gradeInfo['end_time'] ?? 0;
            if (is_string($endTime) && $endTime !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $endTime)) {
                $endTime = strtotime($endTime . ' 23:59:59');
            }
            $endTime = (int)$endTime;
            return $gradeLevel > 0 && ($endTime === 0 || $endTime > time());
        } catch (\Throwable $e) {
            return (int)($user->vip_grade ?? 0) > 0;
        }
    }

    private function isPictureInProductField($product, $picId, $field)
    {
        if (!$product || !$field) {
            return false;
        }
        return in_array((int)$picId, $this->normalizeProductPicIds($product->$field ?? ''), true);
    }

    private function isPictureInProduct($product, $picId)
    {
        if (!$product) {
            return false;
        }
        if ($this->isPictureInProductField($product, $picId, 'pic_ids')
            || $this->isPictureInProductField($product, $picId, 'detail_pic_ids')) {
            return true;
        }
        return (bool)WdXcxUserAlbumPic::where('folder_id', (int)$product->id)
            ->where('pic_id', (int)$picId)
            ->find();
    }

    private function assertPictureDownloadVisible($pic, $viewerUid, $targetUserId = 0, $productId = 0)
    {
        $viewerUid = (int)$viewerUid;
        $targetUserId = (int)$targetUserId;
        $productId = (int)$productId;
        if (!$pic || !$viewerUid) {
            throwError('图片不存在');
        }

        $ownerUid = $targetUserId > 0 ? $targetUserId : (int)$pic->uid;
        $isOwner = $viewerUid === $ownerUid;
        if ($ownerUid <= 0) {
            $ownerUid = (int)$pic->uid;
            $isOwner = $viewerUid === $ownerUid;
        }

        if ($productId > 0) {
            $product = WdXcxAlbumFolder::where('id', $productId)
                ->where('uid', $ownerUid)
                ->where('folder_type', 2)
                ->find();
            if (!$product) {
                throwError('产品不存在');
            }
            if (!$this->isPictureInProduct($product, (int)$pic->id)) {
                throwError('无权下载该图片');
            }
            if (!$isOwner && (int)$product->private_type === 2) {
                throwError('此内容为私有，请勿访问');
            }
            if (!$isOwner) {
                $owner = WdXcxUser::find($ownerUid);
                if (!$owner || (int)$owner->visit_allow_save_pic !== 1) {
                    throwError('商户未开放保存权限');
                }
            }
            if (!$isOwner && (int)$product->hide_detail_pictures === 1 && $this->isPictureInProductField($product, (int)$pic->id, 'detail_pic_ids')) {
                throwError('详情图已被隐藏');
            }
            return;
        }

        if ((int)$pic->uid === $viewerUid) {
            return;
        }

        if ($ownerUid > 0) {
            $owner = WdXcxUser::find($ownerUid);
            if (!$owner || !$owner->is_show_home) {
                throwError('该用户未公开主页');
            }
            $this->assertHomeVisitRequirement($owner, $viewerUid, $isOwner, false);
            if (!$isOwner && (int)$owner->visit_allow_save_pic !== 1) {
                throwError('商户未开放保存权限');
            }
            $product = $this->findVisibleHomeProductByPicture($ownerUid, (int)$pic->id, $viewerUid, $isOwner);
            if (!$product) {
                throwError('分享链接无效');
            }
            if (!$isOwner && (int)$product->hide_detail_pictures === 1 && $this->isPictureInProductField($product, (int)$pic->id, 'detail_pic_ids')) {
                throwError('详情图已被隐藏');
            }
            return;
        }

        $ownedRelation = WdXcxUserAlbumPic::where('pic_id', (int)$pic->id)
            ->where('user_id', $viewerUid)
            ->find();
        if ($ownedRelation) {
            return;
        }

        throwError('无权下载该图片');
    }

    private function originalDownloadUrlForPicture($pic)
    {
        if (!$pic) {
            return '';
        }
        if (method_exists($pic, 'isImportedResourcePicture') && $pic->isImportedResourcePicture()) {
            return (new \app\common\service\album\AiResourceBridgeService($this->app))->getPictureResourceImageUrl($pic, 'original');
        }
        $type = (int)$pic->getData('type');
        if ($type === 5) {
            $config = cacheRemoteSet((int)($pic->uniacid ?: $this->uniacid));
            $cos = $config['cos'] ?? null;
            if ($cos && !empty($cos['bucket']) && !empty($cos['region']) && !empty($cos['ak']) && !empty($cos['sk'])) {
                $key = trim((string)$pic->getData('imgurl'), '/');
                if (!empty($cos['folder_name']) && strpos($key, trim((string)$cos['folder_name'], '/') . '/') !== 0) {
                    $key = trim((string)$cos['folder_name'], '/') . '/' . $key;
                }
                $signedUrl = (new TencentCOSService($cos, $key))->getSignedObjectUrl('+10 minutes');
                if ($signedUrl !== '') {
                    return $signedUrl;
                }
            }
        }
        return removePicStyle($pic->TruePic);
    }

    private function resolveOriginalDownloadFileSize($pic, $url = '')
    {
        $remoteSize = $this->resolveRemoteContentLength($url);
        if ($remoteSize > 0) {
            return $remoteSize;
        }
        return (int)$pic->getData('size');
    }

    private function resolveRemoteContentLength($url)
    {
        $url = trim((string)$url);
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return 0;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'JiafangyunOriginalDownload/1.0',
        ]);
        curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $length = (float)curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        if ($status >= 200 && $status < 400 && $length > 0) {
            return (int)$length;
        }
        return 0;
    }

    public function getOriginalDownloadUrl($param, $userId)
    {
        $picId = (int)($param['pic_id'] ?? ($param['id'] ?? 0));
        if ($picId <= 0) {
            throwError('图片不存在');
        }
        $pic = WdXcxPic::where('id', $picId)->find();
        if (!$pic) {
            throwError('图片不存在');
        }
        $viewer = WdXcxUser::where('id', (int)$userId)->find();
        if (!$viewer) {
            throwError('请先授权登录');
        }
        if (!$this->isActiveMember($viewer)) {
            throwError('请先升级成为会员');
        }

        $targetUserId = (int)($param['target_user_id'] ?? ($param['uid'] ?? 0));
        $productId = (int)($param['product_id'] ?? ($param['folder_id'] ?? 0));
        $this->assertPictureDownloadVisible($pic, (int)$userId, $targetUserId, $productId);

        $url = \normalizePublicAssetUrl($this->originalDownloadUrlForPicture($pic));
        if ($url === '') {
            throwError('原图暂不可下载');
        }

        $fileSize = $this->resolveOriginalDownloadFileSize($pic, $url);
        $shouldRecordTraffic = !array_key_exists('record_traffic', $param) || (int)$param['record_traffic'] !== 0;
        $traffic = null;
        if ($shouldRecordTraffic) {
            $traffic = $this->recordDownloadTraffic([
                'pic_id' => $picId,
                'file_url' => $url,
                'file_size' => $fileSize,
            ], $userId);
        }

        return [
            'pic_id' => $picId,
            'url' => $url,
            'download_url' => $url,
            'downloadUrl' => $url,
            'file_name' => $this->originalDownloadFilename($pic, $url),
            'fileName' => $this->originalDownloadFilename($pic, $url),
            'file_size' => $fileSize,
            'traffic' => $traffic,
            'expires_in' => 600,
        ];
    }

    public function getOriginalZipDownloadItems($param, $userId)
    {
        $picIds = $param['pic_ids'] ?? ($param['picIds'] ?? []);
        if (is_string($picIds)) {
            $decoded = json_decode($picIds, true);
            if (is_array($decoded)) {
                $picIds = $decoded;
            } else {
                $picIds = explode(',', $picIds);
            }
        }
        if (!is_array($picIds)) {
            $picIds = [];
        }
        $picIds = array_values(array_unique(array_filter(array_map('intval', $picIds))));
        if (empty($picIds)) {
            throwError('请选择要下载的图片');
        }
        if (count($picIds) > 60) {
            throwError('单次最多下载60张图片');
        }

        $items = [];
        foreach ($picIds as $picId) {
            $download = $this->getOriginalDownloadUrl(array_merge($param, [
                'pic_id' => $picId,
                'record_traffic' => 0,
            ]), $userId);
            $items[] = [
                'pic_id' => (int)$download['pic_id'],
                'url' => (string)($download['download_url'] ?? ($download['downloadUrl'] ?? ($download['url'] ?? ''))),
                'file_name' => (string)($download['file_name'] ?? ($download['fileName'] ?? ('图片-' . $picId . '.jpg'))),
                'file_size' => (int)($download['file_size'] ?? 0),
            ];
        }

        return $items;
    }

    private function originalDownloadFilename($pic, $url)
    {
        $name = trim((string)$pic->getData('pic_name'));
        if ($name === '') {
            $name = '图片-' . (int)$pic->id;
        }
        if (pathinfo($name, PATHINFO_EXTENSION) === '') {
            $path = (string)parse_url((string)$url, PHP_URL_PATH);
            $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
            if ($ext !== '' && preg_match('/^[a-z0-9]{2,5}$/i', $ext)) {
                $name .= '.' . $ext;
            }
        }
        return $name;
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
        $homeShareCode = $this->ensureHomeShareCode($user);
        $inviteCode = $this->ensureInviteCode($user);
        $title = ($user->company_name ?: $user->nickname) . '的主页';
        $miniPath = $this->normalizeMiniProgramPath($path ?: 'pages/index/index');
        $pagePath = $miniPath['path'];
        $params = $miniPath['params'];
        $params['uid'] = (string)$targetUserId;
        if ($inviteCode) {
            $params['invite_code'] = (string)$inviteCode;
        }

        $query = http_build_query($params);
        $mini_path = $query ? ($pagePath . '?' . $query) : $pagePath;
        $pcLink = $this->buildHomeWebShareUrl($homeShareCode);

        try {
            $share_link = (new WxService())->generateUrlLink($pagePath, $query);
        } catch (\Throwable $e) {
            $share_link = (new WxService())->generateShortLink('/' . $mini_path, $title, false);
        }

        $scene = $query ?: ('uid=' . $targetUserId);
        return [
            'share_link' => $share_link,
            'link' => $share_link,
            'url_link' => $share_link,
            'pc_link' => $pcLink,
            'web_link' => $pcLink,
            'web_url' => $pcLink,
            'share_code' => $homeShareCode,
            'code' => $homeShareCode,
            'invite_code' => $inviteCode,
            'mini_path' => $mini_path,
            'scene' => $scene,
            'title' => $title,
        ];
    }

    private function normalizeMiniProgramPath($path)
    {
        $path = trim((string)$path);
        $path = ltrim($path, '/');
        if ($path === '') {
            $path = 'pages/index/index';
        }

        $parts = parse_url($path);
        $pagePath = isset($parts['path']) && $parts['path'] !== '' ? ltrim($parts['path'], '/') : 'pages/index/index';
        $params = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        foreach ($params as $key => $value) {
            if (is_array($value) || $value === null || $value === '') {
                unset($params[$key]);
            }
        }

        return [
            'path' => $pagePath,
            'params' => $params,
        ];
    }

    private function buildMiniProgramSharePayload($targetUserId, $path = '', $type = 'home', $id = 0, $inviteCode = '')
    {
        $miniPath = $this->normalizeMiniProgramPath($path ?: 'pages/index/index');
        $pagePath = $miniPath['path'];
        $params = $miniPath['params'];
        $params['uid'] = (string)$targetUserId;
        if ($inviteCode) {
            $params['invite_code'] = (string)$inviteCode;
        }
        if ($type !== 'home' && $id) {
            $params['type'] = (string)$type;
            $params['id'] = (string)$id;
        }

        $query = http_build_query($params);
        $sceneParams = ['uid' => (string)$targetUserId];
        if ($type !== 'home' && $id) {
            $sceneParams['id'] = (string)$id;
        }
        if ($type === 'home' && $inviteCode) {
            $inviteScene = http_build_query($sceneParams + ['invite_code' => (string)$inviteCode]);
            if (strlen($inviteScene) <= 32) {
                $sceneParams['invite_code'] = (string)$inviteCode;
            }
        }
        return [
            'page_path' => $pagePath,
            'query' => $query,
            'scene' => http_build_query($sceneParams),
            'mini_path' => $query ? ($pagePath . '?' . $query) : $pagePath,
            'invite_code' => $inviteCode,
        ];
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
        $homeShareCode = $this->ensureHomeShareCode($user);
        $inviteCode = $this->ensureInviteCode($user);
        $sharePayload = $this->buildMiniProgramSharePayload($targetUserId, $path, $type, $id, $inviteCode);
        $file_path = public_path() . 'image/ewm';
        $file_name = 'home_share_' . $type . '_' . $targetUserId . '_' . (int)$id . '_' . md5($sharePayload['mini_path']) . '.jpg';
        $qrcode_path = $file_path . '/' . $file_name;
        
        $data = [
            'scene' => $sharePayload['scene'],
            'path' => $sharePayload['page_path'],
            'filename' => $file_name,
            'filepath' => $file_path,
        ];
        try {
            (new WxService())->getUnlimitQrcode($data);
        } catch (\Throwable $e) {
            Log::error('getHomeMiniProgramCode Error: ' . $e->getMessage());
            throwError('小程序码生成失败');
        }
        if (!file_exists($qrcode_path)) {
            Log::error('getHomeMiniProgramCode file missing: ' . $qrcode_path);
            throwError('小程序码生成失败');
        }

        $displayMeta = $this->getShareDisplayMeta($user, $type, $id);
        $qrcode = ROOT_HOST . '/api/common/ewm?filename=' . $file_name;
        
        return [
            'qrcode' => $this->safeString($qrcode),
            'tips' => $this->safeString($displayMeta['tips'] ?? ''),
            'show_name' => $this->safeString($displayMeta['show_name'] ?? ''),
            'qrcode_path' => $this->safeString($qrcode_path),
            'mini_path' => $this->safeString($sharePayload['mini_path']),
            'scene' => $this->safeString($sharePayload['scene']),
            'share_code' => $this->safeString($homeShareCode),
            'code' => $this->safeString($homeShareCode),
            'invite_code' => $this->safeString($sharePayload['invite_code'] ?? ''),
        ];
    }

    public function getHomeSharePoster($targetUserId, $type = 'home', $id = 0, $path = '', $visitorUid = 0, $coverUrl = '')
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
                Log::error('getHomeSharePoster MiniCode Error: ' . $e->getMessage());
                throwError('小程序码生成失败');
            }
            
            // 生成海报
            $qrInput = isset($code['qrcode_path']) && file_exists($code['qrcode_path']) ? $code['qrcode_path'] : (isset($code['qrcode']) ? $code['qrcode'] : '');
            if (!$qrInput) {
                throwError('小程序码生成失败');
            }
            $share_thumb = $this->generateHomeSharePosterImage($targetUserId, $qrInput, $type, $id, $visitorUid, $coverUrl);
            if (!$share_thumb) {
                throwError('海报生成失败');
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

            return $result;
        } catch (\Throwable $e) {
            Log::error('getHomeSharePoster Fatal Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            throwError('海报生成失败');
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
        $this->userModel->ensureUploadPasswordColumns();

        if (!empty($param['upload_pwd'])) {
            if (!preg_match('/^[A-Za-z0-9]{4}$/', $param['upload_pwd'])) {
                throwError('协同编辑密码需为4位字母或数字');
            }
        }
        if (isset($param['upload_pwd_expire_time']) && $param['upload_pwd_expire_time'] !== null && $param['upload_pwd_expire_time'] !== '') {
            if (!is_numeric($param['upload_pwd_expire_time']) || (int)$param['upload_pwd_expire_time'] < 0) {
                throwError('协同编辑密码有效期不合法');
            }
            $param['upload_pwd_expire_time'] = (int)$param['upload_pwd_expire_time'];
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
            if ($param['upload_pwd'] === '') {
                $user->upload_pwd_expire_time = 0;
            }
        }
        if ((!isset($param['upload_pwd']) || $param['upload_pwd'] !== '') && isset($param['upload_pwd_expire_time']) && $param['upload_pwd_expire_time'] !== null && $param['upload_pwd_expire_time'] !== '') {
            $user->upload_pwd_expire_time = (int)$param['upload_pwd_expire_time'];
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

    /**PC端更新主页设置
     * @param $param
     * @param $uid
     * @return void
     * @throws \cores\exception\BaseException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updatePcSettings($param, $uid)
    {
        $this->userModel->ensureHomePreferenceColumns();
        $user = $this->userModel->getUserById($uid);
        if (!$user) {
            throwError('用户不存在');
        }

        if (isset($param['visit_no_need_nickname']) && $param['visit_no_need_nickname'] !== null) {
            $user->visit_no_need_nickname = (int)$param['visit_no_need_nickname'];
        }
        if (isset($param['visit_no_need_mobile']) && $param['visit_no_need_mobile'] !== null) {
            $user->visit_no_need_mobile = (int)$param['visit_no_need_mobile'];
        }
        if (isset($param['visit_allow_save_pic']) && $param['visit_allow_save_pic'] !== null) {
            $user->visit_allow_save_pic = (int)$param['visit_allow_save_pic'];
        }
        if (isset($param['home_watermark_text']) && $param['home_watermark_text'] !== null) {
            $user->home_watermark_text = $param['home_watermark_text'];
        }
        if (isset($param['home_service_name']) && $param['home_service_name'] !== null) {
            $user->home_service_name = $param['home_service_name'] === '' ? '服务' : $param['home_service_name'];
        }
        if (isset($param['home_share_title']) && $param['home_share_title'] !== null) {
            $user->home_share_title = $param['home_share_title'];
        }
        if (isset($param['home_share_desc']) && $param['home_share_desc'] !== null) {
            $user->home_share_desc = $param['home_share_desc'];
        }
        if (isset($param['home_share_image']) && $param['home_share_image'] !== null) {
            $user->home_share_image = $param['home_share_image'];
        }
        if (isset($param['company_name']) && $param['company_name'] !== null) {
            $user->company_name = $param['company_name'];
        }
        if (isset($param['company_logo']) && $param['company_logo'] !== null) {
            $user->company_logo = $param['company_logo'];
        }
        if (isset($param['company_desc']) && $param['company_desc'] !== null) {
            $user->company_desc = $param['company_desc'];
        }
        if (isset($param['contact_mobile']) && $param['contact_mobile'] !== null) {
            $user->contact_mobile = $param['contact_mobile'];
        }
        if (isset($param['contact_wechat']) && $param['contact_wechat'] !== null) {
            $user->contact_wechat = $param['contact_wechat'];
        }
        if (isset($param['address_province']) && $param['address_province'] !== null) {
            $user->address_province = $param['address_province'];
        }
        if (isset($param['address_city']) && $param['address_city'] !== null) {
            $user->address_city = $param['address_city'];
        }
        if (isset($param['address_district']) && $param['address_district'] !== null) {
            $user->address_district = $param['address_district'];
        }
        if (isset($param['address_detail']) && $param['address_detail'] !== null) {
            $user->address_detail = $param['address_detail'];
        }
        if (isset($param['is_show_home']) && $param['is_show_home'] !== null) {
            $user->is_show_home = (int)$param['is_show_home'];
        }
        if (isset($param['industry_info']) && $param['industry_info'] !== null && $param['industry_info'] !== '') {
            $industry = (int)$param['industry_info'];
            if (!in_array($industry, [0, 1, 2, 3], true)) {
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
	                    $imageUrls = buildPictureImageUrls($item);
	                    $item->isChecked = false;
	                    $item->upload_time = date('Y年m月d日 H:i', $item->getData('create_time'));
	                    $item->nickname = $item->UserInfo['nickname'];
	                    $item->pic_id = $item->id;
	                    $item->picture_url = $imageUrls['preview'];
	                    $item->picture_url_original = $imageUrls['origin'] ?: removePicStyle($imageUrls['preview']);
	                    $item->thumbnail_url = $imageUrls['thumb'];
	                    $item->preview_url = $imageUrls['preview'];
	                    $item->file_url = $imageUrls['origin'];
	                    $item->image_urls = $imageUrls;
	                    $item->imageUrls = $imageUrls;
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
        $this->clearExpiredRecycleProducts($user_id);

        $limit = isset($params['limit']) ? $params['limit'] : 30;
        $key = isset($params['key']) ? $params['key'] : '';

        $query = WdXcxAlbumFolder::onlyTrashed()
            ->where('uid', $user_id);

        if ($key) {
            $query->where('folder_name', 'like', "%{$key}%");
        }

        $lists = $query->order('id desc')
            ->field('id, folder_name, folder_type, new_thumb, pic_ids, detail_pic_ids, create_time, delete_time')
            ->paginate($limit)->each(function ($item){
                $thumb = $item->NewThumb;
                if (!$thumb && $item->folder_type == 2) {
                    $picIds = [];
                    if ($item->pic_ids) {
                        $picIds = array_merge($picIds, explode(',', $item->pic_ids));
                    }
                    if ($item->detail_pic_ids) {
                        $picIds = array_merge($picIds, explode(',', $item->detail_pic_ids));
                    }
                    $picIds = array_values(array_filter(array_map('intval', $picIds)));
                    if (!empty($picIds)) {
                        $pic = WdXcxPic::withTrashed()
                            ->whereIn('id', $picIds)
                            ->orderRaw('FIELD(id, ' . implode(',', $picIds) . ')')
                            ->find();
                        if ($pic) {
                            $thumb = $pic->TruePic;
                        }
                    }
                }
                $item->imgurl = $thumb;
                $item->pic_name = $item->folder_name;
                $item->isChecked = false;
                $item->create_time_str = date('Y/m/d H:i', $item->getData('create_time'));
                $deleteTime = (int)$item->getData('delete_time');
                $item->delete_time_str = $deleteTime > 0 ? date('Y/m/d H:i', $deleteTime) : '';
                $item->expire_time_str = $deleteTime > 0 ? date('Y/m/d H:i', $deleteTime + 30 * 86400) : '';
                unset($item->pic_ids, $item->detail_pic_ids);
            });
        return $lists;
    }

    private function clearExpiredRecycleProducts($user_id)
    {
        $expireTime = time() - 30 * 86400;
        $products = WdXcxAlbumFolder::onlyTrashed()
            ->where('uid', $user_id)
            ->whereNotNull('delete_time')
            ->where('delete_time', '<=', $expireTime)
            ->select();
        $picIds = WdXcxPic::onlyTrashed()
            ->where('uid', $user_id)
            ->whereNotNull('delete_time')
            ->where('delete_time', '<=', $expireTime)
            ->column('id');
        if (count($products) === 0 && empty($picIds)) {
            return 0;
        }
        Db::startTrans();
        try{
            $folders = $this->getRecycleFolderTree($products, $user_id);
            $folderIds = $this->getRecycleFolderIds($folders);
            $count = $this->forceDestroyRecycleProducts($folders, $user_id);
            $count += $this->forceDestroyRecyclePictures($picIds, $user_id, $folderIds);
            Db::commit();
            return $count;
        }catch (\Exception $e){
            Db::rollback();
            Log::error('清理过期回收站失败：' . $e->getMessage());
            return 0;
        }
    }

    private function normalizeRecycleIds($raw)
    {
        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return [];
            }
            if (strpos($raw, '[') !== false) {
                $decoded = json_decode($raw, true);
                $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : explode(',', $raw);
            } else {
                $raw = explode(',', $raw);
            }
        }
        if (!is_array($raw)) {
            $raw = [$raw];
        }
        $ids = [];
        foreach ($raw as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function getRecycleFolderTree($products, $user_id)
    {
        $folders = [];
        foreach ($products as $product) {
            if ($product) {
                $folders[(int)$product->id] = $product;
            }
        }
        $cursor = array_keys($folders);
        while (!empty($cursor)) {
            $children = WdXcxAlbumFolder::onlyTrashed()
                ->where('uid', $user_id)
                ->whereIn('pid', $cursor)
                ->select();
            $cursor = [];
            foreach ($children as $child) {
                $childId = (int)$child->id;
                if (!isset($folders[$childId])) {
                    $folders[$childId] = $child;
                    $cursor[] = $childId;
                }
            }
        }
        return array_values($folders);
    }

    private function getRecycleFolderIds($folders)
    {
        $ids = [];
        foreach ($folders as $folder) {
            if ($folder) {
                $id = (int)$folder->id;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        return array_values(array_unique($ids));
    }

    private function collectRecycleProductPicIds($product)
    {
        if (!$product) {
            return [];
        }
        $ids = array_merge(
            $this->normalizeRecycleIds($product->pic_ids ?? ''),
            $this->normalizeRecycleIds($product->detail_pic_ids ?? '')
        );
        $relationPicIds = WdXcxUserAlbumPic::withTrashed()
            ->where('folder_id', (int)$product->id)
            ->column('pic_id');
        foreach ($relationPicIds as $picId) {
            $picId = (int)$picId;
            if ($picId > 0) {
                $ids[] = $picId;
            }
        }
        return array_values(array_unique($ids));
    }

    private function hasRecyclePictureReferenceOutsideFolders($picId, $folderIds, $user_id)
    {
        $relationQuery = WdXcxUserAlbumPic::withTrashed()
            ->where('pic_id', (int)$picId);
        if (!empty($folderIds)) {
            $relationQuery->whereNotIn('folder_id', $folderIds);
        }
        if ((int)$relationQuery->count() > 0) {
            return true;
        }

        $folderQuery = WdXcxAlbumFolder::withTrashed()
            ->where(function ($query) use ($picId) {
                $query->whereRaw('(FIND_IN_SET(?, pic_ids) OR FIND_IN_SET(?, detail_pic_ids))', [$picId, $picId]);
            });
        if (!empty($folderIds)) {
            $folderQuery->whereNotIn('id', $folderIds);
        }
        return (int)$folderQuery->count() > 0;
    }

    private function prepareRecyclePicturesForRemoteDestroy($picIds, $folderIds, $user_id)
    {
        $deletablePicIds = [];
        foreach (array_values(array_unique(array_map('intval', $picIds))) as $picId) {
            if ($picId <= 0 || $this->hasRecyclePictureReferenceOutsideFolders($picId, $folderIds, $user_id)) {
                continue;
            }
            $pic = WdXcxPic::withTrashed()->where('id', $picId)->find();
            if (!$pic) {
                continue;
            }
            $isTrashed = (int)$pic->getData('delete_time') > 0;
            if ((int)$pic->uid !== (int)$user_id) {
                continue;
            }
            if (!$isTrashed) {
                $pic->delete();
            }
            $deletablePicIds[] = $picId;
        }
        return array_values(array_unique($deletablePicIds));
    }

    private function deleteRemoteRecyclePictures($picIds)
    {
        if (empty($picIds)) {
            return;
        }
        $pics = WdXcxPic::onlyTrashed()
            ->whereIn('id', $picIds)
            ->field('id, uniacid')
            ->select();
        $groups = [];
        foreach ($pics as $pic) {
            $uniacid = (int)$pic->uniacid ?: $this->uniacid;
            if (!isset($groups[$uniacid])) {
                $groups[$uniacid] = [];
            }
            $groups[$uniacid][] = (int)$pic->id;
        }
        $remoteService = new RemoteObjectService($this->app);
        foreach ($groups as $uniacid => $ids) {
            if (!empty($ids)) {
                $remoteService->deleteRemoteObject($uniacid, $ids);
            }
        }
    }

    private function forceDeleteRecycleRelations($folderIds)
    {
        if (empty($folderIds)) {
            return;
        }
        $relations = WdXcxUserAlbumPic::withTrashed()
            ->whereIn('folder_id', $folderIds)
            ->select();
        foreach ($relations as $relation) {
            $relation->force()->delete();
        }
    }

    private function forceDestroyRecycleProducts($products, $user_id)
    {
        $folders = $this->getRecycleFolderTree($products, $user_id);
        if (empty($folders)) {
            return 0;
        }
        $folderIds = [];
        $picIds = [];
        foreach ($folders as $folder) {
            $folderIds[] = (int)$folder->id;
            $picIds = array_merge($picIds, $this->collectRecycleProductPicIds($folder));
        }
        $folderIds = array_values(array_unique(array_filter($folderIds)));
        $deletablePicIds = $this->prepareRecyclePicturesForRemoteDestroy($picIds, $folderIds, $user_id);
        $this->deleteRemoteRecyclePictures($deletablePicIds);
        $this->forceDeleteRecycleRelations($folderIds);
        WdXcxProductCategoryBind::whereIn('category_id', $folderIds)->delete();
        WdXcxProductCategoryBind::whereIn('product_id', $folderIds)->delete();
        foreach ($folders as $folder) {
            $folder->force()->delete();
        }
        return count($folderIds);
    }

    private function forceDestroyRecyclePictures($picIds, $user_id, $ignoreFolderIds = [])
    {
        $deletablePicIds = $this->prepareRecyclePicturesForRemoteDestroy($picIds, $ignoreFolderIds, $user_id);
        $this->deleteRemoteRecyclePictures($deletablePicIds);
        return count($deletablePicIds);
    }

    /**用户还原回收站产品
     * @param $param
     * @param $user_id
     * @return void
     * @throws \cores\exception\BaseException
     */
    public function userRestoreDeleteProducts($param, $user_id)
    {
        $ids = $this->normalizeRecycleIds(isset($param['product_ids']) && !empty($param['product_ids']) ? $param['product_ids'] : (isset($param['pic_ids']) ? $param['pic_ids'] : []));
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
        $ids = $this->normalizeRecycleIds(isset($param['product_ids']) && !empty($param['product_ids']) ? $param['product_ids'] : (isset($param['pic_ids']) ? $param['pic_ids'] : []));
        if(empty($ids)){
            throwError('请选择要删除的产品');
        }

        Db::startTrans();
        try{
            $products = WdXcxAlbumFolder::onlyTrashed()
                ->where('uid', $user_id)
                ->whereIn('id', $ids)
                ->select();
            $this->forceDestroyRecycleProducts($products, $user_id);
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
    }

    /**清空用户回收站
     * @param $user_id
     * @return int
     * @throws \cores\exception\BaseException
     */
    public function clearUserRecycleBin($user_id)
    {
        Db::startTrans();
        try{
            $products = WdXcxAlbumFolder::onlyTrashed()
                ->where('uid', $user_id)
                ->select();
            $folders = $this->getRecycleFolderTree($products, $user_id);
            $folderIds = $this->getRecycleFolderIds($folders);
            $count = $this->forceDestroyRecycleProducts($folders, $user_id);
            $trashPicIds = WdXcxPic::onlyTrashed()
                ->where('uid', $user_id)
                ->column('id');
            $count += $this->forceDestroyRecyclePictures($trashPicIds, $user_id, $folderIds);
        }catch (\Exception $e){
            Db::rollback();
            throwError($e->getMessage());
        }
        Db::commit();
        return $count;
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
                    $item->target_share_code = $targetUser ? $this->getUserShareCodeById($targetUser->id) : '';
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
                    $item->target_share_code = $folder ? $this->getUserShareCodeById($folder->uid) : '';
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
                    $item->target_share_code = $folder ? $this->getUserShareCodeById($folder->uid) : '';
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
                    $item->target_share_code = $targetUser ? $this->getUserShareCodeById($targetUser->id) : '';
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // 2. Product Collections (Products in AlbumFolder: folder_type = 2)
        if ($type == 'all' || $type == 'product' || $type == '产品') {
            $query = WdXcxUserCollectAlbums::where('uid', $uid);
            $fidsQuery = WdXcxAlbumFolder::where('folder_type', 2);
            if ($key) {
                $fidsQuery->where('folder_name', 'like', "%{$key}%");
            }
            $fids = $fidsQuery->column('id');
            if (!empty($fids)) {
                $query->whereIn('fid', $fids);
            } else {
                $query->whereRaw('0');
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
                    $item->target_share_code = $folder ? $this->getUserShareCodeById($folder->uid) : '';
                });
            if (!$records->isEmpty()) {
                $lists = array_merge($lists, $records->toArray());
            }
        }

        // 3. Category Collections (Albums)
        if ($type == 'all' || $type == 'category' || $type == '分类') {
            $query = WdXcxUserCollectAlbums::where('uid', $uid);
            $fidsQuery = WdXcxAlbumFolder::where('folder_type', 1);
            if ($key) {
                $fidsQuery->where('folder_name', 'like', "%{$key}%");
            }
            $fids = $fidsQuery->column('id');
            if (!empty($fids)) {
                $query->whereIn('fid', $fids);
            } else {
                $query->whereRaw('0');
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
                    $item->target_share_code = $folder ? $this->getUserShareCodeById($folder->uid) : '';
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

    /**记录下载外网流量，自己下载和他人下载都记录
     * @param $param
     * @param $user_id
     * @return array
     * @throws \cores\exception\BaseException
     */
    public function recordDownloadTraffic($param, $user_id)
    {
        $picId = (int)($param['pic_id'] ?? ($param['id'] ?? 0));
        if ($picId <= 0) {
            throwError('图片不存在');
        }
        $pic = WdXcxPic::where('id', $picId)->find();
        if (!$pic) {
            throwError('图片不存在');
        }
        $fileSize = (int)($param['file_size'] ?? 0);
        if ($fileSize <= 0) {
            $fileUrl = trim((string)($param['file_url'] ?? ''));
            if ($fileUrl === '') {
                $fileUrl = removePicStyle($pic->TruePic);
            }
            $fileSize = $this->resolveOriginalDownloadFileSize($pic, $fileUrl);
        }
        if ($fileSize <= 0) {
            throwError('文件大小为空');
        }
        $fileUrl = trim((string)($param['file_url'] ?? ''));
        if ($fileUrl === '') {
            $fileUrl = removePicStyle($pic->TruePic);
        }
        $mediaType = ((int)$pic->getData('file_type') === 2) ? 'video' : 'image';

        $result = [
            'pic_id' => $picId,
            'owner_uid' => (int)$pic->uid,
            'file_size' => $fileSize,
            'bridge_synced' => false,
        ];

        try {
            $bridgeClient = new JiafangyunBridgeClient($this->app);
            $owner = $bridgeClient->getUser((int)$pic->uid);
            $payload = array_merge($bridgeClient->userPayload($owner), [
                'owner_b_user_id' => (int)$pic->uid,
                'downloader_b_user_id' => (int)$user_id,
                'b_pic_id' => $picId,
                'file_size' => $fileSize,
                'file_url' => $fileUrl,
                'media_type' => $mediaType,
                'source' => 'pc_album',
                'trace_id' => 'download_' . $user_id . '_' . $picId . '_' . time(),
                'metadata' => [
                    'self_download' => ((int)$pic->uid === (int)$user_id),
                    'downloader_b_user_id' => (int)$user_id,
                ],
            ]);
            $bridgeResp = $bridgeClient->post('/jiafangyun/bridge/traffic/download', $payload);
            $result['bridge_synced'] = true;
            $result['used_traffic_gb'] = (float)($bridgeResp['used_traffic_gb'] ?? 0);
            $result['used_traffic_bytes'] = (int)($bridgeResp['used_traffic_bytes'] ?? 0);
        } catch (\Throwable $e) {
            Log::error('[DownloadTraffic] bridge sync failed: ' . $e->getMessage());
            $message = $e->getMessage() ?: '流量扣减失败，请稍后重试';
            throwError($message);
        }

        try {
            $base = WdXcxBase::where('uniacid', $this->uniacid)->find();
            if ($base) {
                $base->inc('down_count', 1)->update();
            }
        } catch (\Throwable $e) {
            Log::error('[DownloadTraffic] increment down_count failed: ' . $e->getMessage());
        }

        return $result;
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
                return;
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
            if ((int)$folder->uid === (int)$user_id) {
                return;
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
        $type = isset($param['type']) ? $param['type'] : 'visitor';
        $limit = 20;

        if ($type === 'visitor' || $type === 'visitors') {
            $total = WdXcxUserVisitRecord::where('target_uid', $uid)
                ->where('uid', '<>', $uid)
                ->distinct(true)
                ->field('uid')
                ->select()
                ->count();

            $lists = WdXcxUserVisitRecord::where('target_uid', $uid)
                ->where('uid', '<>', $uid)
                ->field('MAX(id) as id, uid, MAX(CASE WHEN update_time > 0 THEN update_time ELSE create_time END) as visit_time')
                ->group('uid')
                ->order('visit_time desc')
                ->page($page, $limit)
                ->select()
                ->each(function($item) {
                    $visitor = WdXcxUser::find($item->uid);
                    $item->visitor_name = $visitor ? ($visitor->nickname ?: $visitor->company_name) : '未知用户';
                    $item->visitor_avatar = $visitor ? $visitor->avatar : '';

                    $item->time = (int)$item->visit_time;
                    $item->time_str = Utils::timeAgo($item->time);
                });

            return [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $limit,
                'data' => $lists
            ];
        }

        $query = WdXcxUserVisitRecord::where('target_uid', $uid)
            ->where('uid', '<>', $uid);
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

    public function markVisitRecordsRead($uid)
    {
        if (!$uid) {
            throwError('用户不存在');
        }
        $user = WdXcxUser::where('id', $uid)->find();
        if (!$user) {
            throwError('用户不存在');
        }
        (new WdXcxUser())->ensureHomePreferenceColumns();
        Db::name('wd_xcx_user')
            ->where('id', $uid)
            ->update(['visit_read_time' => time()]);
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

    private function drawRoundedRect($image, $x1, $y1, $x2, $y2, $radius, $color)
    {
        $x1 = (int)round($x1);
        $y1 = (int)round($y1);
        $x2 = (int)round($x2);
        $y2 = (int)round($y2);
        $radius = max(0, (int)round($radius));
        if ($radius <= 0) {
            \imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color);
            return;
        }
        $radius = min($radius, (int)(($x2 - $x1) / 2), (int)(($y2 - $y1) / 2));
        \imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        \imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        \imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        \imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        \imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        \imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
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
    private function generateHomeSharePosterImage($targetUserId, $qrcodeUrl, $type = 'home', $id = 0, $visitorUid = 0, $coverUrl = '')
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
        \imagealphablending($im, true);
        \imagesavealpha($im, true);

        // Fonts & Colors
        $font = $this->app->getRootPath() . 'public/assets/front/douyuzhuiguangti.ttf';
        $colorBlack = \imagecolorallocate($im, 51, 51, 51);
        $colorGray = \imagecolorallocate($im, 153, 153, 153);
        $colorMuted = \imagecolorallocate($im, 119, 119, 119);
        $colorWhite = \imagecolorallocate($im, 255, 255, 255);
        $colorSoft = \imagecolorallocate($im, 246, 246, 246);
        $colorLine = \imagecolorallocate($im, 232, 224, 214);
        $colorWarm = \imagecolorallocate($im, 255, 250, 226);
        $colorShadow = \imagecolorallocatealpha($im, 0, 0, 0, 112);

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
        $this->drawText($im, $fontSize, 0, $textX, $textY, $colorBlack, $font, $this->clipTextByWidth($nickname, $font, $fontSize, $bgWidth * 0.58));
        $this->drawText($im, $bgWidth * 0.022, 0, $textX, $textY + 42, $colorMuted, $font, '邀请你浏览云相册');

        // 4. Collection Title & Content (Grid or Main Image)
        $collTitle = '可访问相册';
        if ($type === 'home') {
            $collTitle = '可访问相册';
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

        $titleFontSize = $bgWidth * 0.042;
        $titleX = $bgWidth * 0.08;
        $titleY = $avatarY + $avatarSize + 118;
        if ($collTitle) {
            $badgeX = $titleX;
            $badgeY = $titleY - 92;
            $this->drawRoundedRect($im, $badgeX, $badgeY, $badgeX + 178, $badgeY + 42, 21, $colorWarm);
            $this->drawText($im, $bgWidth * 0.02, 0, $badgeX + 22, $badgeY + 29, $colorMuted, $font, '云相册分享');

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
            $this->drawText($im, $titleFontSize, 0, $titleX, $titleY, $colorBlack, $font, $this->clipTextByWidth($collTitle, $font, $titleFontSize, $bgWidth - $titleX - ($bgWidth * 0.08)));
        }

        $this->drawText($im, $bgWidth * 0.024, 0, $bgWidth * 0.08, $titleY + 42, $colorGray, $font, '仅展示当前访问权限可见的相册封面');

        $contentY = $titleY + 76;
        $gridX = $bgWidth * 0.08;
        $gridWidth = $bgWidth - ($gridX * 2);
        $gridHeight = $bgWidth * 0.72;
        $this->drawRoundedRect($im, $gridX + 8, $contentY + 10, $gridX + $gridWidth + 8, $contentY + $gridHeight + 10, 34, $colorShadow);
        $this->drawRoundedRect($im, $gridX - 18, $contentY - 18, $gridX + $gridWidth + 18, $contentY + $gridHeight + 18, 34, $colorWhite);
        \imagerectangle($im, $gridX - 18, $contentY - 18, $gridX + $gridWidth + 18, $contentY + $gridHeight + 18, $colorLine);

        if ($type === 'selection') {
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
            $covers = $this->getSharePosterCovers($targetUserId, $type, $id, $visitorUid, 4);
            $coverUrl = $this->normalizePosterCoverUrl($coverUrl);
            if ($coverUrl) {
                array_unshift($covers, $coverUrl);
            }
            $covers = array_values(array_unique(array_filter($covers)));
            $this->drawPosterCoverGrid($im, $covers, $gridX, $contentY, $gridWidth, $gridHeight, $font, $colorWhite, $colorGray, $colorSoft);
        }

        // 5. Footer (QR Code)
        $qrDrawn = false;
        if ($qrcodeUrl) {
            try {
                $qrContent = file_get_contents($qrcodeUrl);
                if ($qrContent) {
                    $qrImg = \imagecreatefromstring($qrContent);
                    if ($qrImg) {
                        $qrSize = $bgWidth * 0.2;
                        $qrX = $bgWidth - $gridX - $qrSize;
                        $qrY = $bgHeight - $gridX - $qrSize - 15;

                        $qrPad = 14;
                        $this->drawRoundedRect($im, $qrX - $qrPad, $qrY - $qrPad, $qrX + $qrSize + $qrPad, $qrY + $qrSize + $qrPad, 22, $colorWhite);
                        \imagecopyresampled($im, $qrImg, $qrX, $qrY, 0, 0, $qrSize, $qrSize, \imagesx($qrImg), \imagesy($qrImg));
                        \imagedestroy($qrImg);
                        $qrDrawn = true;
                    }
                }
            } catch (\Exception $e) {}
        }
        if (!$qrDrawn) {
            \imagedestroy($im);
            return '';
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
                 \imagedestroy($im);
                 return '';
            }
            \imagedestroy($im);

            return file_exists($filePath) ? (request()->domain() . '/storage/poster/' . $filename) : '';
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

    private function clipText($text, $maxLength)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text, 'utf-8') > $maxLength
                ? mb_substr($text, 0, max(1, $maxLength - 1), 'utf-8') . '...'
                : $text;
        }
        return strlen($text) > $maxLength ? substr($text, 0, max(1, $maxLength - 1)) . '...' : $text;
    }

    private function clipTextByWidth($text, $font, $fontSize, $maxWidth)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }
        if (!function_exists('imagettfbbox') || !file_exists($font)) {
            return $this->clipText($text, 16);
        }
        $measure = function($value) use ($font, $fontSize) {
            $box = @\imagettfbbox($fontSize, 0, $font, $value);
            if (!$box) {
                return strlen($value) * $fontSize;
            }
            return abs($box[2] - $box[0]);
        };
        if ($measure($text) <= $maxWidth) {
            return $text;
        }
        $suffix = '...';
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'utf-8') : strlen($text);
        for ($i = max(1, $length - 1); $i > 0; $i--) {
            $part = function_exists('mb_substr')
                ? mb_substr($text, 0, $i, 'utf-8')
                : substr($text, 0, $i);
            if ($measure($part . $suffix) <= $maxWidth) {
                return $part . $suffix;
            }
        }
        return $suffix;
    }

    private function getSharePosterCovers($targetUserId, $type = 'home', $id = 0, $visitorUid = 0, $limit = 4)
    {
        $visitorUid = (int)$visitorUid;
        $targetUserId = (int)$targetUserId;
        $isOwner = $visitorUid && $visitorUid === $targetUserId;
        $sharedIds = [];
        if (!$isOwner && $visitorUid) {
            try {
                $sharedIds = \app\common\model\album\WdXcxAlbumShareBind::where('bind_uid', $visitorUid)->column('fid');
            } catch (\Throwable $e) {
                $sharedIds = [];
            }
        }
        $sharedIds = array_values(array_unique(array_map('intval', $sharedIds ?: [])));

        if ($type === 'product' && $id) {
            $product = WdXcxAlbumFolder::where('id', (int)$id)
                ->where('uid', $targetUserId)
                ->where('folder_type', 2)
                ->find();
            if (!$product || !$this->canPosterShowFolder($product, false, $sharedIds, true)) {
                return [];
            }
            return $this->getProductPosterCoverUrls($product, $limit);
        }

        $query = WdXcxAlbumFolder::where('uid', $targetUserId)
            ->where('folder_type', 2);

        if ($type === 'category' && $id) {
            $category = WdXcxAlbumFolder::where('id', (int)$id)
                ->where('uid', $targetUserId)
                ->where('folder_type', 1)
                ->find();
            if (!$category || !$this->canPosterShowFolder($category, false, $sharedIds, true)) {
                return [];
            }
            $boundIds = \app\common\model\album\WdXcxProductCategoryBind::where('category_id', (int)$id)
                ->where('userid', $targetUserId)
                ->column('product_id');
            $directIds = WdXcxAlbumFolder::where('uid', $targetUserId)
                ->where('pid', (int)$id)
                ->where('folder_type', 2)
                ->column('id');
            $productIds = array_values(array_unique(array_merge($boundIds ?: [], $directIds ?: [])));
            if (empty($productIds)) {
                return [];
            }
            $query->whereIn('id', $productIds);
        }

        $query = $this->applyVisibleProductScope($query, $isOwner, $sharedIds, false);
        $products = $query
            ->field('id,folder_name,new_thumb,pic_ids,detail_pic_ids,uid,private_type,sort,set_top,set_top_time,is_hot')
            ->order('is_hot desc, sort desc, set_top desc, set_top_time desc, id desc')
            ->limit($limit * 8)
            ->select();

        $covers = [];
        foreach ($products as $product) {
            if (!$this->canPosterShowFolder($product, false, $sharedIds, false)) {
                continue;
            }
            $urls = $this->getProductPosterCoverUrls($product, 1);
            foreach ($urls as $url) {
                $covers[] = $url;
                if (count($covers) >= $limit) {
                    return $covers;
                }
            }
        }
        return $covers;
    }

    private function applyVisibleProductScope($query, $isOwner, $sharedIds, $allowOwnerPrivate = true)
    {
        if ($isOwner && $allowOwnerPrivate) {
            return $query;
        }
        return $query->where(function($q) use ($sharedIds){
            $q->where('private_type', 1);
            if (!empty($sharedIds)) {
                $q->whereOr('private_type', 4);
            }
        });
    }

    private function canPosterShowFolder($folder, $isOwner, $sharedIds, $allowDirectShare = false)
    {
        if ($isOwner && !$allowDirectShare) {
            return true;
        }
        $privateType = (int)($folder->private_type ?? 1);
        if ($privateType === 1) {
            return true;
        }
        if ($privateType !== 4) {
            return false;
        }
        if ($allowDirectShare) {
            return true;
        }
        $sharedIds = array_map('intval', $sharedIds ?: []);
        if (empty($sharedIds)) {
            return false;
        }
        try {
            $folderIds = array_map('intval', $folder->ParentIds ?: [(int)$folder->id]);
        } catch (\Throwable $e) {
            $folderIds = [(int)$folder->id];
        }
        return count(array_intersect($folderIds, $sharedIds)) > 0;
    }

    private function getProductPosterCoverUrls($product, $limit = 4)
    {
        $urls = [];
        if (!empty($product->new_thumb)) {
            $urls[] = $product->new_thumb;
        }
        $picIds = $this->normalizePosterIdList($product->pic_ids ?? '');
        if (!empty($picIds) && count($urls) < $limit) {
            $pics = WdXcxPic::whereIn('id', array_slice($picIds, 0, $limit))->select();
            foreach ($pics as $pic) {
                try {
                    $url = $pic->TruePic;
                } catch (\Throwable $e) {
                    $url = '';
                }
                if ($url) {
                    $urls[] = $url;
                }
                if (count($urls) >= $limit) {
                    break;
                }
            }
        }
        return array_values(array_unique(array_filter($urls)));
    }

    private function normalizePosterIdList($value)
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }
        $text = trim((string)$value);
        if ($text === '') {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $text))));
    }

    private function normalizePosterCoverUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '' || $url === 'null' || $url === 'undefined') {
            return '';
        }
        if (stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            $scheme = parse_url(request()->domain(), PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $url;
        }
        if (strpos($url, '/') === 0) {
            return request()->domain() . $url;
        }
        return $url;
    }

    private function drawPosterCoverGrid($im, $covers, $x, $y, $width, $height, $font, $colorWhite, $colorGray, $colorSoft)
    {
        $gap = 20;
        $mainH = (int)($height * 0.58);
        $thumbH = (int)(($height - $mainH - $gap));
        $thumbW = (int)(($width - ($gap * 2)) / 3);

        if (empty($covers)) {
            \imagefilledrectangle($im, $x, $y, $x + $width, $y + $height, $colorSoft);
            $this->drawText($im, 30, 0, $x + 44, $y + ($height / 2), $colorGray, $font, '暂无可访问相册封面');
            return;
        }

        $first = array_shift($covers);
        $this->drawPosterImageTile($im, $first, $x, $y, $width, $mainH, $colorSoft);

        for ($i = 0; $i < 3; $i++) {
            $tileX = $x + ($i * ($thumbW + $gap));
            $tileY = $y + $mainH + $gap;
            if (isset($covers[$i])) {
                $this->drawPosterImageTile($im, $covers[$i], $tileX, $tileY, $thumbW, $thumbH, $colorSoft);
            } else {
                \imagefilledrectangle($im, $tileX, $tileY, $tileX + $thumbW, $tileY + $thumbH, $colorSoft);
            }
        }
    }

    private function drawPosterImageTile($im, $url, $x, $y, $width, $height, $fallbackColor)
    {
        $img = $this->createImageFromUrl($url);
        if (!$img) {
            \imagefilledrectangle($im, $x, $y, $x + $width, $y + $height, $fallbackColor);
            return;
        }
        $resized = $this->resizeImageCover($img, (int)$width, (int)$height);
        \imagecopy($im, $resized, (int)$x, (int)$y, 0, 0, (int)$width, (int)$height);
        \imagedestroy($img);
        \imagedestroy($resized);
    }

    private function createImageFromUrl($url)
    {
        if (!$url) {
            return null;
        }
        try {
            $content = @file_get_contents($url);
            if (!$content) {
                return null;
            }
            $img = @\imagecreatefromstring($content);
            return $img ?: null;
        } catch (\Throwable $e) {
            return null;
        }
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
