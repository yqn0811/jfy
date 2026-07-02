<?php

namespace app\common\service\album;

use app\common\model\album\WdXcxAlbumFolder;
use app\common\model\album\WdXcxAlbumSelection;
use app\common\model\album\WdXcxAlbumSelectionItem;
use app\common\model\user\WdXcxUser;
use app\common\service\BaseService;
use think\App;
use think\facade\Db;

class SelectionService extends BaseService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    private function ensureSelectionColumns()
    {
        $hasCustomerUid = Db::query("SHOW COLUMNS FROM `wd_xcx_album_selection` LIKE 'customer_uid'");
        if (!$hasCustomerUid) {
            Db::execute("ALTER TABLE `wd_xcx_album_selection` ADD COLUMN `customer_uid` int(11) NOT NULL DEFAULT 0 AFTER `uid`");
            Db::execute("ALTER TABLE `wd_xcx_album_selection` ADD INDEX `idx_customer_uid`(`customer_uid`)");
        }
        $hasFactoryUid = Db::query("SHOW COLUMNS FROM `wd_xcx_album_selection` LIKE 'factory_uid'");
        if (!$hasFactoryUid) {
            Db::execute("ALTER TABLE `wd_xcx_album_selection` ADD COLUMN `factory_uid` int(11) NOT NULL DEFAULT 0 AFTER `customer_uid`");
            Db::execute("ALTER TABLE `wd_xcx_album_selection` ADD INDEX `idx_factory_uid`(`factory_uid`)");
        }
    }

    private function assertSelectionOwner(WdXcxAlbumSelection $selection, $uid)
    {
        $this->ensureSelectionColumns();
        $ownerUid = $this->getSelectionCustomerUid($selection);
        if ($ownerUid != $uid) {
            throwError('无权操作该选款单');
        }
    }

    private function assertSelectionDeletable(WdXcxAlbumSelection $selection, $uid)
    {
        $this->ensureSelectionColumns();
        $uid = (int)$uid;
        $customerUid = $this->getSelectionCustomerUid($selection);
        $factoryUid = $this->getSelectionFactoryUid($selection);
        if ($uid !== $customerUid && (!$factoryUid || $uid !== $factoryUid)) {
            throwError('无权删除该选款单');
        }
    }

    private function getSelectionCustomerUid(WdXcxAlbumSelection $selection)
    {
        return (int)($selection->customer_uid ?: $selection->uid);
    }

    private function getSelectionFactoryUid(WdXcxAlbumSelection $selection)
    {
        $factoryUid = (int)$selection->factory_uid;
        if (!$factoryUid && $selection->product_id) {
            $factoryUid = (int)WdXcxAlbumFolder::where('id', $selection->product_id)->value('uid');
        }
        return $factoryUid;
    }

    private function normalizePicIds($pic_ids)
    {
        if (is_array($pic_ids) && count($pic_ids) === 1 && is_string(reset($pic_ids))) {
            $pic_ids = reset($pic_ids);
        }
        if (is_string($pic_ids)) {
            $decoded = json_decode($pic_ids, true);
            if (is_array($decoded)) {
                $pic_ids = $decoded;
            } else {
                $str = trim($pic_ids, "[] \t\n\r\0\x0B");
                if ($str === '') {
                    $pic_ids = [];
                } elseif (strpos($str, ',') !== false) {
                    $pic_ids = explode(',', $str);
                } else {
                    $pic_ids = [$str];
                }
            }
        }
        if (!is_array($pic_ids)) {
            $pic_ids = [];
        }
        $pic_ids = array_map(function ($v) {
            return (int)trim((string)$v);
        }, $pic_ids);
        $pic_ids = array_values(array_unique(array_filter($pic_ids, function ($v) {
            return $v > 0;
        })));
        return $pic_ids;
    }

    private function getProductDetail($productId, $uid = 0)
    {
        return (new AlbumService($this->app))->getProductDetail($productId, $uid);
    }

    private function buildUserSummary($userId)
    {
        $default = [
            'id' => (int)$userId,
            'nickname' => '匿名用户',
            'avatar' => getLocalImage('/image/users/user_default.png'),
            'mobile' => '',
            'company_name' => '',
            'contact_mobile' => '',
            'contact_wechat' => '',
        ];

        if (!$userId) {
            return $default;
        }

        $user = WdXcxUser::where('id', $userId)->find();
        if (!$user) {
            return $default;
        }

        $info = $user->getUserInfoShow($userId);
        return [
            'id' => (int)$userId,
            'nickname' => $info['nickname'] ?? '匿名用户',
            'avatar' => $info['avatar'] ?? $default['avatar'],
            'mobile' => $info['mobile'] ?? '',
            'company_name' => $info['company_name'] ?? '',
            'contact_mobile' => $info['contact_mobile'] ?? '',
            'contact_wechat' => $info['contact_wechat'] ?? '',
        ];
    }

    private function buildProductSummary($product)
    {
        if (!$product) {
            return null;
        }

        $summary = [
            'id' => (int)$product->id,
            'name' => $product->folder_name,
            'desc' => $product->folder_desc,
            'cover_img' => $product->new_thumb,
            'share_img' => $product->new_thumb,
            'category_ids' => [],
        ];

        if (isset($product->category_ids)) {
            $summary['category_ids'] = is_array($product->category_ids) ? $product->category_ids : [];
        }

        return $summary;
    }

    private function buildSelectionPictureItem($pic, $selectionItem = null)
    {
        if (!$pic) {
            return null;
        }

        return [
            'id' => (int)$pic->id,
            'selection_item_id' => $selectionItem ? (int)$selectionItem->id : 0,
            'src' => $pic->TruePic,
            'imgurl' => $pic->TruePic,
            'name' => $pic->pic_name ?: '',
            'pic_name' => $pic->pic_name ?: '',
            'file_type' => (int)$pic->file_type,
            'product_id' => $selectionItem ? (int)$selectionItem->product_id : 0,
            'is_main' => false,
        ];
    }

    private function buildGroupedPictures($product, array $selectedPictures)
    {
        $mainPictures = [];
        $variantPictures = [];
        $detailPictures = [];
        $selectedMap = [];

        foreach ($selectedPictures as $picture) {
            $selectedMap[$picture['id']] = $picture;
        }

        if ($product && !empty($product->pic_list)) {
            foreach ($product->pic_list as $pic) {
                $picture = $this->buildSelectionPictureItem($pic);
                if (!$picture) {
                    continue;
                }

                if (empty($mainPictures)) {
                    $picture['is_main'] = true;
                    $mainPictures[] = $picture;
                }

                if (isset($selectedMap[$picture['id']])) {
                    $selectedMap[$picture['id']]['is_main'] = false;
                    $variantPictures[] = $selectedMap[$picture['id']];
                    unset($selectedMap[$picture['id']]);
                }
            }
        }

        if (empty($mainPictures) && !empty($selectedPictures)) {
            $selectedPictures[0]['is_main'] = true;
            $mainPictures[] = $selectedPictures[0];
        }

        if ($product && !empty($product->detail_pic_list)) {
            foreach ($product->detail_pic_list as $pic) {
                $picture = $this->buildSelectionPictureItem($pic);
                if ($picture) {
                    $detailPictures[] = $picture;
                }
            }
        }

        foreach ($selectedMap as $picture) {
            $variantPictures[] = $picture;
        }

        return [
            'main_pictures' => array_values($mainPictures),
            'variant_pictures' => array_values($variantPictures),
            'detail_pictures' => array_values($detailPictures),
        ];
    }

    public function createSelection($uid, $product_id, $pic_ids, $factory_uid)
    {
        $this->ensureSelectionColumns();
        if (empty($product_id)) {
            throwError('请选择产品');
        }
        if (!$factory_uid) {
            throwError('请选择厂家');
        }
        if ($factory_uid == $uid) {
            throwError('厂家不能选择自己的产品');
        }

        $pic_ids = $this->normalizePicIds($pic_ids);
        if (empty($pic_ids)) {
            throwError('请选择花色图');
        }
        
        $product = WdXcxAlbumFolder::where('id', $product_id)->find();
        if (!$product) {
            throwError('产品不存在');
        }
        if ($product->uid != $factory_uid) {
            throwError('产品不属于该厂家');
        }

        $name = date('Ymd') . ' FST' . rand(10, 99);
        $selection = WdXcxAlbumSelection::create([
            'uid' => $uid,
            'customer_uid' => $uid,
            'factory_uid' => $factory_uid,
            'product_id' => $product_id,
            'name' => $name,
            'uniacid' => request()->uniacid ?? 0
        ]);
        $data = [];
        foreach ($pic_ids as $pid) {
            $data[] = [
                'selection_id' => $selection->id,
                'product_id' => $product_id,
                'pic_id' => $pid
            ];
        }
        if (!empty($data)) {
            (new WdXcxAlbumSelectionItem())->saveAll($data);
        }
        return $selection;
    }

    public function getSelectionDetail($selection_id)
    {
        $selection = WdXcxAlbumSelection::find($selection_id);
        if (!$selection) {
            throwError('选款单不存在');
        }
        $this->ensureSelectionColumns();
        if ($selection->customer_uid && $selection->customer_uid != $selection->uid) {
            $selection->uid = $selection->customer_uid;
        }
        $selection->display_time = $selection->create_time;
        $items = WdXcxAlbumSelectionItem::where('selection_id', $selection_id)
            ->with(['pic'])
            ->select();
        $list = [];
        $coverImg = [];
        foreach ($items as $item) {
            if ($item->pic) {
                $temp = $this->buildSelectionPictureItem($item->pic, $item);
                $list[] = $temp;
                $coverImg[] = $temp['src'];
            }
        }

        $product = $selection->product_id ? $this->getProductDetail($selection->product_id, $selection->customer_uid ?: $selection->uid) : null;
        if ($product) {
            if ($product->folder_type == 2) {
                $product->img_url = $product->getData('new_thumb');
            } else {
                $product->img_url = $product->new_thumb;
            }
        }

        $customerUid = $selection->customer_uid ?: $selection->uid;
        $factoryUid = $selection->factory_uid ?: ($product ? $product->uid : 0);
        $groupedPictures = $this->buildGroupedPictures($product, $list);
        $shareImage = '';
        if (!empty($groupedPictures['main_pictures'])) {
            $shareImage = $groupedPictures['main_pictures'][0]['src'];
        } elseif (!empty($coverImg)) {
            $shareImage = $coverImg[0];
        } elseif ($product && !empty($product->img_url)) {
            $shareImage = $product->img_url;
        }

        return [
            'info' => $selection,
            'customer' => $this->buildUserSummary($customerUid),
            'factory' => $this->buildUserSummary($factoryUid),
            'product' => $product,
            'product_summary' => $this->buildProductSummary($product),
            'list' => $list,
            'cover_img' => $coverImg,
            'share_img' => $shareImage,
            'grouped_pictures' => $groupedPictures,
            'total_selected' => count($list),
        ];
    }

    public function getMySelectionLists($param, $uid)
    {
        $this->ensureSelectionColumns();
        $limit = $param['limit'] ?? 10;
        $lists = WdXcxAlbumSelection::where('customer_uid', $uid)
            ->order('id', 'desc')
            ->paginate($limit)
            ->each(function ($item) {
                $detail = $this->getSelectionDetail($item->id);
                $pics = $detail['list'] ?? [];
                $item->title = $item->name;
                $item->display_time = $item->create_time;
                $item->product_count = count($pics);
                $item->cover_img = $detail['cover_img'] ?? [];
                $item->share_img = $detail['share_img'] ?? '';
                $item->customer = $detail['customer'] ?? [];
                $item->factory = $detail['factory'] ?? [];
                $item->product = $detail['product_summary'] ?? null;
                $item->selected_preview = array_slice($pics, 0, 3);
            });
        return $lists;
    }

    public function getCustomerSelectionLists($param, $uid)
    {
        $this->ensureSelectionColumns();
        $limit = $param['limit'] ?? 10;
        $lists = WdXcxAlbumSelection::where('factory_uid', $uid)
            ->order('id', 'desc')
            ->paginate($limit)
            ->each(function ($item) {
                $detail = $this->getSelectionDetail($item->id);
                $pics = $detail['list'] ?? [];
                $item->title = $item->name;
                $item->display_time = $item->create_time;
                $item->product_count = count($pics);
                $item->cover_img = $detail['cover_img'] ?? [];
                $item->share_img = $detail['share_img'] ?? '';
                $item->customer = $detail['customer'] ?? [];
                $item->factory = $detail['factory'] ?? [];
                $item->product = $detail['product_summary'] ?? null;
                $item->selected_preview = array_slice($pics, 0, 3);
            });
        return $lists;
    }

    public function updateSelectionName($selection_id, $uid, $name)
    {
        if (!$selection_id) {
            throwError('请选择选款单');
        }
        $selection = WdXcxAlbumSelection::find($selection_id);
        if (!$selection) {
            throwError('选款单不存在');
        }
        $this->assertSelectionOwner($selection, $uid);
        if ($name !== '' && $name !== null) {
            $selection->name = $name;
            $selection->save();
        }
    }

    public function addSelectionImages($selection_id, $uid, $pic_ids)
    {
        if (!$selection_id) {
            throwError('请选择选款单');
        }
        $selection = WdXcxAlbumSelection::find($selection_id);
        if (!$selection) {
            throwError('选款单不存在');
        }
        $this->assertSelectionOwner($selection, $uid);
        $pic_ids = $this->normalizePicIds($pic_ids);
        if (empty($pic_ids)) {
            throwError('请选择花色图');
        }

        $existing = WdXcxAlbumSelectionItem::where('selection_id', $selection_id)->column('pic_id');
        $toAdd = array_diff($pic_ids, $existing);
        
        if (empty($toAdd)) {
            return;
        }
        
        $data = [];
        foreach ($toAdd as $pid) {
            $data[] = [
                'selection_id' => $selection_id,
                'product_id' => $selection->product_id,
                'pic_id' => $pid
            ];
        }
        if (!empty($data)) {
            (new WdXcxAlbumSelectionItem())->saveAll($data);
        }
    }

    public function removeSelectionImages($selection_id, $uid, $pic_ids)
    {
        if (!$selection_id) {
            throwError('请选择选款单');
        }
        $selection = WdXcxAlbumSelection::find($selection_id);
        if (!$selection) {
            throwError('选款单不存在');
        }
        $this->assertSelectionOwner($selection, $uid);
        $pic_ids = $this->normalizePicIds($pic_ids);
        if (empty($pic_ids)) {
            throwError('请选择花色图');
        }

        WdXcxAlbumSelectionItem::where('selection_id', $selection_id)
            ->whereIn('pic_id', $pic_ids)
            ->delete();
    }

    public function deleteSelection($selection_id, $uid)
    {
        if (!$selection_id) {
            throwError('请选择选款单');
        }
        $selection = WdXcxAlbumSelection::find($selection_id);
        if (!$selection) {
            throwError('选款单不存在');
        }
        $this->assertSelectionDeletable($selection, $uid);
        Db::transaction(function () use ($selection, $selection_id) {
            WdXcxAlbumSelectionItem::where('selection_id', $selection_id)->delete();
            $selection->delete();
        });
    }
}
