<?php

namespace cores;

class ExcelCommon
{
    private $objPHPExcel;
    private $readObjPHPExcel;
    private $column = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CC', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DA', 'DD', 'DD', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ'];

    /**导出订单Excel
     * @param $data
     * @param $title
     * @param $filename
     * @param $width
     * @param $height
     * @return void
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportExcel(
        $data,
        $title,
        $filename,
        $total_msg = '',
        $width = 20,
        $height = 60
    )
    {
        $top = $total_msg ? 2 : 1;
        $first_line = $top + 1;
        $this->getExcelObj($filename, $width, $height)
            ->setTotalData($total_msg, $title)
            ->setExcelTitle($title, $top)
            ->setExcelData($data, $title, $first_line)->export($filename);
    }

    /**下载导入使用的Excel模板
     * @param $data
     * @param $title
     * @param $filename
     * @param $width
     * @param $height
     * @return void
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function excelModel(
        $data,
        $title,
        $filename,
        $width = 20,
        $height = 60
    )
    {
        $title_line = isset($data['flag']) ? 6 : 5;
        $this->getExcelObj($filename, $width, $height)
            ->setTopMsg($data)
            ->setExcelTitle($title, $title_line, true)
            ->exportNew($filename);
    }

    /**读取Excel数据
     * @param $file_path
     * @param $type
     * @param $data
     * @return array
     * @throws \PHPExcel_Reader_Exception
     */
    public function getReadDataFromExcel($file_path, $type, $data)
    {
        return $this->getReadExcelObj($file_path, $type)->getExcelDataFromFile($data);
    }

    /**组装读取的数据
     * @param $data
     * @return array
     */
    private function getExcelDataFromFile($data)
    {
        $sheet = $this->readObjPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $excel_data = [];
        if ($highestRow >= $data['first_line']) {
            for ($i = $data['first_line']; $i <= $highestRow; $i++) {
                $temp = [];
                for ($j = 0; $j < $data['field_counts']; $j++) {
                    $value = $this->readObjPHPExcel->getActiveSheet()->getCell($this->column[$j] . $i)->getValue();
                    $temp[] = $value ? $value : '';
                }
                $excel_data[] = $temp;
            }
        }
        return $excel_data;
    }

    /**设置顶部标题
     * @param $title
     * @return $this
     */
    public function setExcelTitle($title, $top = 1, $blod = false)
    {
        foreach ($title as $k => $item) {
            $this->objPHPExcel->getActiveSheet()->setCellValue($this->column[$k] . $top, $item);
            if ($blod) {
                //字体加粗
                $this->objPHPExcel->getActiveSheet()->getStyle($this->column[$k] . $top)->getFont()->setBold(true);
            }
        }
        return $this;
    }

    /**设置模版提示语
     * @param $data
     * @return $this
     */
    private function setTopMsg($data)
    {
        $count = isset($data['flag']) ? count($data['flag']) - 1 : 4;
        $this->objPHPExcel->getActiveSheet()->mergeCells('A1:' . $this->column[$count] . '5');
        $this->objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $data['msg']);
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->getColor()->setRGB('ff0000');
        //设置文本内容左对齐
        $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

//        if(isset($data['flag'])){
//            $this->setExcelTitle($data['flag'], 6);
//        }
        return $this;
    }

    private function setTotalData($total_data, $title)
    {
        if($total_data){
            $count = count($title) - 1;
            $this->objPHPExcel->getActiveSheet()->mergeCells('A1:' . $this->column[$count] . '1');
            $this->objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $total_data);
            $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
            $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->getColor()->setRGB('ff0000');
            //设置文本内容左对齐
            $this->objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        return $this;
    }

    /**设置订单数据
     * @param $data
     * @param $title
     * @return $this
     */
    private function setExcelData($data, $title, $first_line = 2)
    {
        foreach ($data as $k => $item) {
            foreach ($title as $kk => $title_value) {
                $value = $item[$title_value];
                if ($value['need_merge']) {
                    if ($value['can_show']) {
                        //合并单元格（如果要拆分单元格是需要先合并再拆分的，否则程序会报错）
                        $this->objPHPExcel->getActiveSheet()->mergeCells($this->column[$kk] . ($k + $first_line) . ':' . $this->column[$kk] . ($k + ($first_line - 1) + $item['son']));
                        if (!empty($value['is_num'])) {
                            $this->objPHPExcel->setActiveSheetIndex(0)->setCellValue($this->column[$kk] . ($k + $first_line), $value['value']);
                        } else {
                            $this->objPHPExcel->setActiveSheetIndex(0)->setCellValue($this->column[$kk] . ($k + $first_line), $value['value'], 's');
                        }
                    }
                } else {
                    if (!empty($value['is_num'])) {
                        $this->objPHPExcel->getActiveSheet()->setCellValue($this->column[$kk] . ($k + $first_line), $value['value']);
                    } else {
                        $this->objPHPExcel->getActiveSheet()->setCellValueExplicit($this->column[$kk] . ($k + $first_line), $value['value'], 's');
                    }
                }
                if (!empty($value['need_brek'])) {
                    $this->objPHPExcel->getActiveSheet()->getStyle($this->column[$kk] . ($k + $first_line))->getAlignment()->setWrapText(true);
                }
            }
        }
        return $this;
    }

    /**导出
     * @param $filename
     * @return void
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function export($filename)
    {
        ob_end_clean();
        $this->objPHPExcel->getActiveSheet()->setTitle('导出' . $filename);
        $this->objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**导出  xlsx 格式
     * @param $filename
     * @return void
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function exportNew($filename)
    {
        $this->objPHPExcel->getActiveSheet()->setTitle('导出' . $filename);
        $this->objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    /**获取Excel对象
     * @param $filename
     * @param $width
     * @param $height
     * @return $this
     * @throws \PHPExcel_Exception
     */
    private function getExcelObj($filename, $width, $height)
    {
        require_once ROOT_PATH . 'public/plugin/PHPExcel/PHPExcel.php';
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("导出" . $filename)
            ->setLastModifiedBy($filename)
            ->setTitle("导出" . $filename)
            ->setSubject("导出" . $filename)
            ->setDescription("导出" . $filename)
            ->setKeywords("导出" . $filename)
            ->setCategory("导出" . $filename);
        //所有单元格（列）默认宽度
        $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth($width);
        //所有单元格（行）默认宽度
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight($height);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $this->objPHPExcel = $objPHPExcel;

        return $this;
    }

    /**获取读取Excel对象
     * @param $file_path
     * @param $type
     * @return $this
     * @throws \PHPExcel_Reader_Exception
     */
    private function getReadExcelObj($file_path, $type)
    {
        require_once ROOT_PATH . 'public/plugin/PHPExcel/PHPExcel.php';
        if ($type == 'XLSX') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        } else {
            $objReader = \PHPExcel_IOFactory::createReader($type);
        }
        $objPHPExcel = $objReader->load($file_path, $encode = 'utf-8');
        $this->readObjPHPExcel = $objPHPExcel;
        return $this;
    }


}