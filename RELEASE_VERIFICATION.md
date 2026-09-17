# 本版上线核验清单

## 版本范围

- 项目：`tmp/jfy` 家纺云相册
- 当前开发分支：`develop`
- 文档日期：2026-09-13
- 本版纳入：分类上级关系、PC/小程序分类与产品一致性、仅分享可见产品的管理态显示
- 本版不纳入：微信扫码登录后页面不自动进入的问题，另行排期
- 发布环境：未指定前只允许本地或测试环境验证，不得直接发布正式环境

## 当前实施状态（2026-09-13）

- [x] PC 分类新建、编辑已增加上级分类选择，支持设置为顶级分类。
- [x] 后端已接收 `pid`，并校验父级归属、自引用和后代循环引用。
- [x] PC 分类树、产品筛选和产品编辑已递归读取子分类。
- [x] 产品分类关系已兼容产品自身 `pid`、正常绑定及 `userid=0` 历史绑定，并校验 owner 归属。
- [x] PC 管理列表已固定使用 JWT 当前用户的 owner-only 查询，保留 `private_type=1/2/4`。
- [x] `album-web` 已通过 `pnpm build`（包含 `vue-tsc --noEmit` 和 Vite 生产构建）。
- [x] `miniapp` 已通过 `npm run build:mp-weixin`。
- [x] `git diff --check` 已通过。
- [ ] PHP 语法检查：本机无 PHP，Docker 服务未启动，需在测试或发布环境补跑。
- [ ] 数据库只读核查及 owner、普通访客、合法分享三类身份回归仍待测试环境执行。

## 一、分类上级关系

### 功能范围

- PC 分类新建支持选择“顶级分类”或已有分类作为上级分类。
- PC 分类编辑支持修改上级分类。
- 工作台新建分类与分类管理页使用同一套上级分类规则。
- 小程序分类编辑不能产生与 PC 不一致的父子关系。

### 前端核验

- [ ] `CategoryEditDialog` 显示上级分类选择器。
- [ ] 新建分类选择父级后，保存请求包含正确的 `pid`。
- [ ] 编辑分类变更父级后，保存请求包含新的 `pid`。
- [ ] 选择“顶级分类”时发送 `pid=0`。
- [ ] 当前分类及其所有后代不会出现在可选父级中。
- [ ] 分类树刷新后层级、展开状态和产品数量正常。

### 后端核验

- [ ] 分类编辑接口接收并处理 `pid`。
- [ ] 父级必须存在、属于当前用户且 `folder_type=1`。
- [ ] 禁止自引用和后代引用，避免形成循环树。
- [ ] 不需要新增数据库字段，继续使用 `wd_xcx_album_folder.pid`。

## 二、PC/小程序分类与产品一致性

### 数据核查（只读）

针对问题分类和产品执行以下检查，正式修复前必须记录异常数量：

```sql
SELECT id, uid, folder_type, folder_name, pid, private_type, delete_time
FROM wd_xcx_album_folder
WHERE id IN (...分类ID..., ...产品ID...);

SELECT product_id, category_id, userid
FROM wd_xcx_product_category_bind
WHERE product_id IN (...产品ID...)
   OR category_id IN (...分类ID...);

SELECT product_id, category_id, userid
FROM wd_xcx_product_category_bind
WHERE userid = 0
  AND (product_id IN (...产品ID...) OR category_id IN (...分类ID...));
```

重点确认：

- [ ] 子分类的 `uid` 与父分类、当前用户一致。
- [ ] 子分类的 `pid` 指向正确的分类。
- [ ] 产品的 `uid` 与当前登录用户一致。
- [ ] 产品是否只有 `pid`，或只有绑定表记录。
- [ ] 绑定表是否存在 `userid=0` 或错误 owner 的历史记录。
- [ ] 产品和分类没有被软删除（`delete_time` 为空）。

### 接口与前端核验

- [ ] 分类树接口返回根分类和递归 `children`。
- [ ] 产品分类关系同时兼容产品 `pid` 和绑定表 `category_id`。
- [ ] 产品列表、产品编辑详情、分类产品计数使用同一套分类关系。
- [ ] PC 分类筛选能筛出小程序已存在的产品。
- [ ] 分类数量与实际产品列表数量一致，且去重。
- [ ] 旧绑定记录兼容时必须以产品 owner 和分类 owner 校验归属。
- [ ] 新写入的绑定记录必须保存正确的 `userid`。

### 历史数据修复门槛

- [ ] 先输出待修复记录和影响行数。
- [ ] 修复脚本可重复执行，不产生重复绑定。
- [ ] 测试环境验证通过后再考虑正式环境。
- [ ] 不自动猜测不存在分类的归属，异常记录单独报告。

## 三、仅分享可见产品

### 可见性规则

| 访问者 | 公开 `private_type=1` | 私密 `private_type=2` | 仅分享 `private_type=4` |
| --- | --- | --- | --- |
| 产品 owner 管理后台 | 可见 | 可见 | 可见 |
| 普通访客 | 可见 | 不可见 | 不可见 |
| 合法分享访问 | 可见 | 不可见 | 仅可见被分享内容 |

### 管理后台核验

- [ ] owner 的“全部产品”包含公开、私密、仅分享可见三种状态。
- [ ] 默认状态筛选为“全部”时不排除 `private_type=4`。
- [ ] 仅分享产品在分类筛选中也能显示。
- [ ] 分类产品数量包含 owner 的 `private_type=4` 产品。
- [ ] 请求使用 JWT 当前用户 UID，不误带 `target_uid` 或 `target_user_id`。
- [ ] 产品不在回收站，且 `delete_time` 为空。

### 访客权限核验

- [ ] 普通访客不能通过首页列表看到未分享的 `private_type=4` 产品。
- [ ] 合法分享链接可以看到对应的 `private_type=4` 产品。
- [ ] `private_type=2` 对非 owner 始终不可见。
- [ ] 不能通过产品详情直达接口绕过分享权限。

## 四、上线前验证命令

在 `tmp/jfy` 子仓库执行，未确认环境前不执行发布命令：

```bash
cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/album-web
pnpm build

cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/backend
php -l app/api/controller/AlbumApiController.php
php -l app/common/service/album/AlbumService.php

cd /Users/mac/Documents/trae_projects/sub2api/ai_jf/tmp/jfy/miniapp
npm run build:mp-weixin
```

## 五、发布门槛

- [ ] 已完成数据库只读核查。
- [ ] 已完成分类父级新建、编辑、移动和循环校验。
- [ ] 已完成 PC/小程序分类和产品一致性回归。
- [ ] 已完成 owner、普通访客、合法分享三种可见性回归。
- [ ] 已确认项目、环境、域名和固定目标目录。
- [ ] 前端发布前已执行 `rsync --dry-run`。
- [ ] 未将本版未纳入的扫码登录问题误标记为已修复。

## 关联代码

- `album-web/src/components/category_management/CategoryEditDialog.vue`
- `album-web/src/components/category_management/CategoryList.vue`
- `album-web/src/components/management_workbench/WorkbenchOverview.vue`
- `album-web/src/components/product_management/ProductManagementContent.vue`
- `album-web/src/lib/jfyuntu-mappers.ts`
- `backend/app/api/controller/AlbumApiController.php`
- `backend/app/common/service/album/AlbumService.php`
- `backend/app/common/model/album/WdXcxAlbumFolder.php`
