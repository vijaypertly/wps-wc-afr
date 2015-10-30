<?php

if(class_exists('VjGrid')){
    return;
}

class VjGrid{
    public $filter_datas = array();
    public $params = array();
    public $is_ajax = true;
    public $is_show_query_details = true;
    public $get_default_table = true;
    public $action_from = '';
    public $ajax_onclick_function = 'loadData';
    public $ajax_disp_on = '';
    public $query = '';
    public $query_count = '';
    public $display_coloumns = array();
    public $filter_coloumns = array();
    public $page = '';
    public $records_per_page = 5;
    public $total_rows = 0;
    public $pagination_next_icon = '&raquo;';
    public $pagination_prev_icon = '&laquo;';
    public $device = 'pc';
    public $arr_cals = array();
    public $mod_row_data_fn = '';
    public $mod_row_class_fn = '';
    public $mod_row_data_last_fn = '';
    public $after_buttons_html = '';
    public $next_to_buttons_html = '';
    public $html_before_filters = '';
    public $filters_name_concat = 'wps_wc_afr_';
    public $row_checkbox_sel_value = '';
    public $is_checkboxes = false;

    public static function formatDataForSql($data){
        if(!empty($data)){
            if(is_array($data)){
                $arr = array();
                foreach($data as $ky=>$dta){
                    if(is_array($dta)){
                        $arr[$ky] = self::formatDataForSql($dta);
                    }
                    else{
                        $arr[$ky] = self::cleanData($dta);
                    }
                }
                return $arr;
            }
            else if(is_string($data)){
                return self::cleanData($data);
            }
        }
        return $data;
    }

    public static function cleanData($val=''){
        return esc_sql($val);
    }

    public function generateTable(){
        $arr_cals = $this->calculatePaginateCriteria();
        $limit_query = " LIMIT ".$arr_cals['limit_from'].', '.$arr_cals['limit_to'];
        $query = $this->query();
        if(!empty($query)){
            $query .= $limit_query;
            $data = $this->fetchData($query);

            if($this->get_default_table == true){
                return $this->defaultTable($data);
            }

            $html_file = dirname(__FILE__).DIRECTORY_SEPARATOR."_grid_html".DIRECTORY_SEPARATOR.$this->action_from.".php";
            if(file_exists($html_file)){
                ob_start();
                require_once $html_file;
                $html_content = ob_get_contents();
                ob_end_clean();
            }
            if(!empty($html_content)){
                return $html_content;
            }
        }
        return false;
    }

    public function tableRowLast($data=array(), $row_no=''){
        if(!empty($data) && !empty($this->mod_row_data_last_fn)){
            $mod_row_data_last_fn = $this->mod_row_data_last_fn;
            if(function_exists($mod_row_data_last_fn)){
                return $mod_row_data_last_fn($data, $row_no);
            }
        }
        return '';
    }

    public function tableRowData($key='', $data=array()){
        if(!empty($key) && !empty($this->mod_row_data_fn)){
            $mod_row_data_fn = $this->mod_row_data_fn;
            if(function_exists($mod_row_data_fn)){
                return $mod_row_data_fn($key, $data);
            }
        }
        return $data[$key];
    }

    public function tableRowClass($row_no='', $data=array()){
        if(!empty($row_no) && !empty($this->mod_row_class_fn)){
            $mod_row_class_fn = $this->mod_row_class_fn;
            if(function_exists($mod_row_class_fn)){
                return $mod_row_class_fn($row_no, $data);
            }
        }
        return '';
    }

    public function defaultTable($data=array()){
        $html_file = dirname(__FILE__).DIRECTORY_SEPARATOR."_grid_html".DIRECTORY_SEPARATOR."_default_table".".php";
        if(file_exists($html_file)){
            ob_start();
            require_once $html_file;
            $html_content = ob_get_contents();
            ob_end_clean();
        }
        if(!empty($html_content)){
            return $html_content;
        }
        return '';
    }

    public function fetchData($query=''){
        global $wpdb;
        if(!empty($query)){
            $rws = $wpdb->get_results($query, ARRAY_A);
            if(!empty($rws)){
                return $rws;
            }
        }
        return array();
    }

    public function query(){
        $query = $this->query;
        return $query;
    }

    public function setPage($page='1'){
        if($page<=0){
            $this->page = 1;
        }
        else{
            $this->page = $page;
        }
    }

    public function totalNoOfRecords(){
        global $wpdb;
        if(!empty($this->query_count)){
            $total_rows = $wpdb->get_var($this->query_count);
            if(!empty($total_rows)){
                $this->total_rows = $total_rows;
                return $total_rows;
            }
        }
        $this->total_rows = 0;
        return 0;
    }

    public function calculatePaginateCriteria(){
        $arr = array();
        $total_rows = $this->totalNoOfRecords();
        $cur_page = $this->page;
        $records_per_page = $this->records_per_page;

        $total_pages = ceil($total_rows/$records_per_page);

        if($cur_page<=0){
            $cur_page = 1;
            $this->page = $cur_page;
        }
        $arr['limit_from'] = ($cur_page-1)*$records_per_page;
        $arr['limit_to'] = $records_per_page;
        $arr['total_rows'] = $total_rows;
        $arr['cur_page'] = $cur_page;
        $arr['records_per_page'] = $records_per_page;
        $arr['total_pages'] = $total_pages;
        $this->arr_cals = $arr;
        return $arr;
    }

    public function getPaginationUrl($page_name='', $page='', $query_str=''){
        if($this->is_ajax == true){
            return 'javascript:void(0);';
        }
        else{
            return "".$page_name."?page=".$page.$query_str."";
        }
    }

    public function paginationAjaxOnClick($page){
        if($this->is_ajax == true){
            return ' onclick="'.$this->ajax_onclick_function.'(\''.$this->action_from.'\', \''.$this->ajax_disp_on.'\', \'page\', \''.$page.'\');"';
        }
        return '';
    }

    public function createPagination($noofrecord, $fecth_limit, $cur_page='1', $page_name='', $query_str=''){
        $pagenumber = '';
        $pageflag = 0;
        $numberofpages = 0;
        if($noofrecord){
            if($noofrecord % $fecth_limit == 0){
                $numberofpages = intval($noofrecord / $fecth_limit);
            }
            else {
                $numberofpages = intval ($noofrecord / $fecth_limit);
                $remaning	  = ($noofrecord % $fecth_limit);
                if($remaning>0){
                    $numberofpages = $numberofpages + 1;
                }
            }
        }
        $tpage = 1;
        if($cur_page){
            $tpage = $cur_page;
        }

        if($numberofpages > 1 ){
            for($i=1;$i<=$numberofpages;$i++){
                $tempcount = $i;
                if($tpage == $i){
                    if($i > 0){
                        $z = $i-1;
                        $x = 0;
                        for($j = $z ; $j > 0 ; $j--){
                            if(($x != 3)){
                                // $arr_page[$j] = "<li><a title='".$j."' href='".$page_name."?page=".$j.$query_str."'>$j</a></li>";
                                $arr_page[$j] = "<li><a title='".$j."' ".$this->paginationAjaxOnClick($j)." href='".$this->getPaginationUrl($page_name, $j, $query_str)."'>$j</a></li>";
                                $pageflag = 1;
                            }else{
                                break;
                            }
                            $x++;
                        }
                    }
                    $arr_page[$i] = "<li><a class='active' style='background-color: #a3a2a0; color: #000000' title='".$i."'>$i</a></li>";
                    $pageflag = 1;
                    ksort($arr_page);
                    $pagenumber .= implode( "",$arr_page);
                }
                else {
                    if($i == ($tpage+1)){
                        $pagenumber .= "";
                        // $pagenumber .=  "<li><a title='Page ".$tempcount."' href='".$page_name."?page=".$i.$query_str."'>$i</a></li>";
                        $pagenumber .=  "<li><a title='Page ".$tempcount."' ".$this->paginationAjaxOnClick($i)." href='".$this->getPaginationUrl($page_name, $i, $query_str)."'>$i</a></li>";
                        $pageflag = 1;
                    }
                    if($i == ($tpage+2)){
                        $pagenumber .= "";
                        // $pagenumber .=  "<li><a title='Page ".$tempcount."'  href='".$page_name."?page=".$i.$query_str."'>$i</a></li>";
                        $pagenumber .=  "<li><a title='Page ".$tempcount."' ".$this->paginationAjaxOnClick($i)."  href='".$this->getPaginationUrl($page_name, $i, $query_str)."'>$i</a></li>";
                        $pageflag = 1;
                    }
                    if($i == ($tpage+3)){
                        $pagenumber .= "";
                        // $pagenumber .=  "<li><a title='Page ".($tempcount)."'  href='".$page_name."?page=".$i.$query_str."'>$i</a></li>";
                        $pagenumber .=  "<li><a title='Page ".($tempcount)."' ".$this->paginationAjaxOnClick($i)."  href='".$this->getPaginationUrl($page_name, $i, $query_str)."'>$i</a></li>";
                        $pageflag = 1;
                    }
                }
                if($i!=$numberofpages){
                    //$pagenumber .= " | ";
                }
            }
        }
        if($cur_page) {
            $page = $cur_page;
            if($page ==1){
                $start = 0;
            } else {
                $start =  ($page -1) * $fecth_limit;
            }
        }
        $previouspage =  "";
        $nextpage =  "";
        if($cur_page > 1){
            $ppage = $cur_page - 1;
            if($ppage!=1 && $ppage!=2 && $ppage!=3){
                // $previouspage .= "<li><a  class=\"prev\" href='".$page_name."?page=1".$query_str."' title='First'>First</a></li>";
                // $previouspage .= "<li><a  class=\"prev\" ".$this->paginationAjaxOnClick(1)." href='".$this->getPaginationUrl($page_name, 1, $query_str)."' title='First'>First</a></li>";
            }
            // $previouspage .= "<li><a  class=\"prev\" href='".$page_name."?page=".$ppage.$query_str."' title='Previous'>&laquo;</a></li>";
            $previouspage .= "<li><a  class=\"prev\" ".$this->paginationAjaxOnClick($ppage)." href='".$this->getPaginationUrl($page_name, $ppage, $query_str)."' title='Previous'>".$this->pagination_prev_icon."</a></li>";
            $pageflag = 1;
        }
        else{
            if(@$ppage!=''){$previouspage = "<li><a  class=\"prev-no\" title='Previous'>Previous</a></li>";	}
        }
        if($cur_page){
            $npage = $cur_page + 1;
        }
        else {
            $npage = 2;
        }
        if($npage <= $numberofpages){
            //$tt = $numberofpages + 1;
            // $nextpage .= "<li><a class=\"next\" href='".$page_name."?page=".$npage.$query_str."' title='Next'>&raquo;</a></li>";
            $nextpage .= "<li><a class=\"next\" ".$this->paginationAjaxOnClick($npage)." href='".$this->getPaginationUrl($page_name, $npage, $query_str)."' title='Next'>".$this->pagination_next_icon."</a></li>";
            if($npage!=$numberofpages-1 && $npage!=$numberofpages-2 && $npage!=$numberofpages){
                // $nextpage .= "<li><a class=\"next\" href='".$page_name."?page=".$numberofpages.$query_str."' title='Last'>Last</a></li>";
                // $nextpage .= "<li><a class=\"next\" ".$this->paginationAjaxOnClick($numberofpages)." href='".$this->getPaginationUrl($page_name, $numberofpages, $query_str)."' title='Last'>Last</a></li>";
            }
            $pageflag = 1;
        }
        else {
            if($npage!=''){
                //$nextpage =  "<li><a class=\"next-no\" title='Next'>Last</a></li>";
            }
        }
        return '<ul class="pagination">'.$previouspage.$pagenumber.$nextpage."</ul> ";
    }

    public function generateFilters(){
        $arr = array();
        if(!empty($this->filter_coloumns) && is_array($this->filter_coloumns)){
            foreach($this->filter_coloumns as $name=>$filter){
                if(!empty($filter['type'])){
                    $vl = $filter['default_value'];
                    if(!empty($this->filter_datas[$name])){
                        $vl = $this->filter_datas[$name];
                    }

                    $name = $this->filters_name_concat.$name;

                    if($filter['type'] == 'date_picker'){
                        $arr[] = array(
                            'name'=>$name,
                            'label'=>$filter['label'],
                            'field'=>'<input class="ves_dtpck" id="inp_id_'.$name.'" type="text" name="'.$name.'" value="'.$vl.'">',
                        );
                    }
                    else if($filter['type'] == 'select'){
                        if(!empty($filter['options']) && is_array($filter['options'])){
                            $sb = '';
                            $sb .= '<select id="inp_id_'.$name.'" class="inp_cls_'.$name.'" name="'.$name.'">';
                            foreach($filter['options'] as $sel_ke=>$sel_name){
                                if($vl == $sel_ke){
                                    $selth = ' SELECTED="SELECTED" ';
                                }
                                else{
                                    $selth = '';
                                }
                                $sb .= '<option '.$selth.' value="'.$sel_ke.'">'.$sel_name.'</option>';
                            }
                            $sb .= '</select>';
                        }
                        if(!empty($sb)){
                            $arr[] = array(
                                'name'=>$name,
                                'label'=>$filter['label'],
                                'field'=>$sb,
                            );
                        }
                    }
                    else if($filter['type'] == 'text'){
                        $arr[] = array(
                            'name'=>$name,
                            'label'=>$filter['label'],
                            'field'=>'<input type="text" id="inp_id_'.$name.'" class="inp_cls_'.$name.'" name="'.$name.'" value="'.$vl.'">',
                        );
                    }
                }
            }
        }
        return $arr;
    }

    public function addAfterButtons(){
        if(!empty($this->after_buttons_html)){
            return $this->after_buttons_html;
        }
        return '';
    }

    public function addNextToButtons(){
        if(!empty($this->next_to_buttons_html)){
            return $this->next_to_buttons_html;
        }
        return '';
    }

    public function addHtmlBeforeFilters(){
        if(!empty($this->html_before_filters)){
            return $this->html_before_filters;
        }
        return '';
    }
}