<?php defined( 'ABSPATH' ) or die(''); ?>
<div class="dvb_grid">
	<?php echo $this->addHtmlBeforeFilters(); ?>
	<?php require '_default_filters.php'; ?>
	<?php if($this->is_show_query_details === true){ //echo '<pre>'; print_r($this->arr_cals);?> <div class="dvb_grid_dtls"><?php $arr_cals = $this->arr_cals; echo " Total records matched: ".$arr_cals['total_rows'].". Viewing page ".$this->page." of ".$arr_cals['total_pages']."."; ?></div> <?php } ?>
	<table class="table">
		<thead>
			<tr>
				<?php if($this->is_checkboxes === true){ echo '<th><input type="checkbox" id="anvchk_all" name="anvchk_all"/></th>'; } ?>
				<?php
					if(!empty($this->display_coloumns) && is_array($this->display_coloumns)){
						foreach($this->display_coloumns as $dc){
							//vimal - 28-10-2014 to resize reference field
							if($dc=='Reference'){
								echo "<th style='width:100px;'>$dc</th>";
							}else{
								echo "<th>$dc</th>";
							}	
						}
					}
				?>
			</tr>
		</thead>
		<tbody>
			<?php
				$i = 0;
				if(!empty($data) && !empty($this->display_coloumns) && is_array($this->display_coloumns)){
					foreach($data as $dta){
						$i++;
						$tr_class = $this->tableRowClass($i, $dta);
						echo "
							<tr class=\"".$tr_class."\">
						";
						if($this->is_checkboxes === true){ 
							$chk_bx_id = $i;
							if(isset($dta[$this->row_checkbox_sel_value])){
								if(is_string($dta[$this->row_checkbox_sel_value])){
									$chk_bx_id = $dta[$this->row_checkbox_sel_value];
								}
							}
							echo '<td><input type="checkbox" class="clsvchk_bx" value="on" name="'.$this->filters_name_concat.'vchk_bx['.$chk_bx_id.']"/></td>'; 
						}
						foreach($this->display_coloumns as $ky=>$dc){
							if(isset($dta[$ky])){
								$dc_val = $this->tableRowData($ky, $dta);
								echo "<td>$dc_val</td>";
							}
						}
						echo $this->tableRowLast($dta, $i);
						echo "
							</tr>
						";
					}
				}
				else{
					echo "<tr><td colspan=\"".(count($this->display_coloumns)+1)."\">No results found.</td></tr>";
				}
			?>
		</tbody>
		<?php 
		$arr_cals = $this->arr_cals;
		$pagination = $this->createPagination($arr_cals['total_rows'], $arr_cals['records_per_page'], $arr_cals['cur_page']);
		if(!empty($pagination)){
		?>
		<tfoot>
			<tr>
				<td colspan="<?php echo count($this->display_coloumns); ?>"> <?php echo $pagination; ?> </td>
			</tr>
		</tfoot>
		<?php } ?>
	</table>
	<script>
		jQuery(document).ready(function(){
			if(jQuery('.vespgrid_ajax_popup_link').length>0){ jQuery('.vespgrid_ajax_popup_link').magnificPopup({ type: 'ajax', closeBtnInside: true }); }
			if(jQuery('#anvchk_all').length>0){ 
				jQuery('#anvchk_all').click(function(){
					if(jQuery('#anvchk_all').prop("checked")){//Checked
						var is_chk = true;
					}
					else{
						var is_chk = false;
					}
					jQuery('#<?php echo $this->ajax_disp_on; ?>'+' input[class="clsvchk_bx"]').prop('checked',is_chk);
				});
			}
		});
	</script>
</div>