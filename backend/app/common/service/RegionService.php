<?php

namespace app\common\service;

use app\common\model\system\WdXcxRegion;
use think\facade\Db;

class RegionService extends BaseService
{
    public function importData()
    {
        // 1. Create Table
        $sql = "CREATE TABLE IF NOT EXISTS `wd_xcx_region` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `code` varchar(20) NOT NULL DEFAULT '' COMMENT '行政区划代码',
          `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
          `parent_code` varchar(20) NOT NULL DEFAULT '0' COMMENT '父级代码',
          `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '层级 1省 2市 3区',
          `pinyin` varchar(100) DEFAULT '' COMMENT '拼音',
          PRIMARY KEY (`id`),
          KEY `idx_code` (`code`),
          KEY `idx_parent_code` (`parent_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='中国省市区数据';";
        
        try {
            Db::execute($sql);
        } catch (\Exception $e) {
            // Table might exist
        }

        // Check if data exists
        if (WdXcxRegion::count() > 0) {
            return ['msg' => '数据已存在，无需导入'];
        }

        // 2. Read JSON
        $jsonFile = app()->getRootPath() . 'runtime/data.json';
        if (!file_exists($jsonFile)) {
            // Try to find in public or other places if not found
            if (file_exists(root_path() . 'public/data.json')) {
                $jsonFile = root_path() . 'public/data.json';
            } else {
                 throw new \Exception("数据文件不存在: " . $jsonFile);
            }
        }

        $json = file_get_contents($jsonFile);
        $data = json_decode($json, true);

        if (!$data) {
            throw new \Exception("JSON解析失败");
        }

        // Build code map for existence check
        $codeMap = [];
        foreach ($data as $item) {
            if (isset($item['code'])) {
                $codeMap[$item['code']] = true;
            }
        }

        $insertData = [];
        foreach ($data as $item) {
            // Filter out town/street level
            if (isset($item['town']) && $item['town'] != '0' && $item['town'] !== 0) {
                continue;
            }

            $code = $item['code'];
            $name = $item['name'];
            $province = $item['province'] ?? '';
            $city = $item['city'] ?? '';
            $area = $item['area'] ?? '';

            $level = 1;
            $parent_code = '0';

            // Logic to determine level and parent
            if ($province && (empty($city) || $city == '0') && (empty($area) || $area == '0')) {
                // Province (Level 1)
                $level = 1;
                $parent_code = '0';
            } else {
                // City or District
                // Try to construct parent code
                // If it is a District (area != 0), parent usually is Province + City + 00
                // If it is a City (city != 0, area == 0), parent usually is Province + 0000
                
                if (!empty($area) && $area != '0') {
                    // It is a District
                    $candidateParent = $province . $city . '00'; // e.g. 130100
                    
                    if (isset($codeMap[$candidateParent])) {
                        $parent_code = $candidateParent;
                        // Parent is likely Level 2 (City)
                        $level = 3;
                    } else {
                        // Parent City node doesn't exist (e.g. Municipalities 110101 -> 110100 missing)
                        // Fallback to Province
                        $parent_code = $province . '0000';
                        $level = 2; // Direct child of Province
                    }
                } elseif (!empty($city) && $city != '0') {
                    // It is a City (or similar Level 2)
                    $parent_code = $province . '0000';
                    $level = 2;
                }
            }
            
            // Safety check: if parent_code doesn't exist (and not 0), verify?
            // But we already checked existence for candidate.
            
            $insertData[] = [
                'code' => $code,
                'name' => $name,
                'parent_code' => $parent_code,
                'level' => $level,
                'pinyin' => '' // Pinyin not in data.json, can implement pinyin lib if needed later
            ];
        }

        // Batch Insert
        if (!empty($insertData)) {
            $chunks = array_chunk($insertData, 1000);
            foreach ($chunks as $chunk) {
                WdXcxRegion::insertAll($chunk);
            }
        }
        
        return ['msg' => '导入成功', 'count' => count($insertData)];
    }

    public function getRegionTree()
    {
        try {
            $count = WdXcxRegion::count();
        } catch (\Exception $e) {
            $count = 0;
        }

        if ($count == 0) {
            $this->importData();
        }

        $list = WdXcxRegion::field('code as value, name as label, parent_code')
            ->order('code', 'asc')
            ->select()
            ->toArray();

        return $this->buildTree($list);
    }

    private function buildTree(array $list)
    {
        $tree = [];
        $items = [];
        
        // Key by value (code)
        foreach ($list as $item) {
            $items[$item['value']] = $item;
        }
        
        foreach ($items as $key => &$item) {
            if ($item['parent_code'] == '0') {
                $tree[] = &$item;
            } else {
                if (isset($items[$item['parent_code']])) {
                    $items[$item['parent_code']]['children'][] = &$item;
                }
            }
        }
        
        return $tree;
    }
}
