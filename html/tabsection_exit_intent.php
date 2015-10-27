<?php defined( 'ABSPATH' ) or die(''); ?>
<?php
$opts = get_option('wps_ei_settings'); 				
?>
<div class="wrap">       		
<form action="<?php echo admin_url(); ?>options.php" method="post" >	
	<?php settings_fields('wps_ei_options');?>
	<table style="margin-left:30px;" class="form-table">
		<tbody>
		<tr>
			<th scope="row"><label for="">Exit Intent Title : </label></th>
			<td><input type="text" name="wps-ei-title" value="<?php echo esc_html($opts['wps-ei-title']); ?>" /></td>
		</tr>		
		<tr>
			<td colspan="2">
				<p class="submit"><input type="submit" class="button-primary" name="ct_admin_options" value="<?php esc_attr_e('Save Changes') ?>" /></p>
			</td>
		</tr>         				               				
		</tbody>
	</table>
</form>
</div>