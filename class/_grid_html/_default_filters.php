<?php defined( 'ABSPATH' ) or die(''); ?>
<?php 

$filters = $this->generateFilters();
if(!empty($filters) && is_array($filters)){
	?>
	<div class="dvb_filters">
		<?php 
		foreach($filters as $filter){
			echo "<div class=\"sing_filter cls_span_".@$filter['name']."\"><div class=\"dvb_filter_label\">".$filter['label']."</div> <div class=\"dvb_filter_field\">".$filter['field']."</div></div>";
		}
		?>
		<span class="vespfbuttons">
			<!-- <div class="vespfbuttons_btnlabel">&nbsp;</div> -->
			<div class="vespfbuttons_btnfield">
				<span class="sesubmit"><input class="outline vespseclk" type="submit" value="Filter" onclick="<?php echo $this->ajax_onclick_function."('".$this->action_from."', '".$this->ajax_disp_on."')"; ?>"></span>
				<span class="sereset"><input class="outline vespseclk" type="submit" value="Reset" onclick="<?php echo $this->ajax_onclick_function."('".$this->action_from."', '".$this->ajax_disp_on."', 'reset')"; ?>"></span>
				<?php echo $this->addNextToButtons(); ?>
			</div>
		</span>
	</div>
	<?php
}
?>
<?php echo $this->addAfterButtons(); ?>
<script>
jQuery(document).ready(function() {
	if(jQuery('.ves_dtpck').length>0){
		jQuery('.ves_dtpck').each(function() {
			jQuery(this).datepicker({ dateFormat: 'dd-mm-yy' });
		});
	}
});
</script>