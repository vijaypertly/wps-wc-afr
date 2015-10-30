<?php defined( 'ABSPATH' ) or die(''); ?>
<?php	
	global $post;
	
	$data = get_option('wps_wc_afr_settings');
	if(empty($data)){
		$data = array(
			'enable_cron'=> true,
			'send_mail_to_admin_after_recovery'=> true,
			'admin_email'=> get_option( 'admin_email' ),
			'cron_time_in_minutes'=> 15,
			'abandoned_time_in_minutes'=> 15,
			//'consider_un_recovered_order_after_minutes'=> 2*24*60,
			'consider_un_recovered_order_after'=> '2',
			'consider_un_recovered_order_after_time_type'=> 'days',
		);
	}
	$time_types = array('mins'=> 'Minutes','hours' => 'Hours','days' => 'Days');
?>
<div class="complete-wrap">
	<h1 id="js-add-new-template">Settings</h1>
	<p>set your settings</p>	

	<div class="js-error"></div>
	<form id="js-afrsettings" action="javascript:void(0);" name="afrsettings" method="post">
		<table class="form-table">
			<tbody>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="enable_cron">Is Enable Cron <span class="description">(required)</span></label>
					</th>
					<td>
						<input type="checkbox" id="enable_cron" name="data[enable_cron]" value="true" <?php if (isset($data['enable_cron']) && $data['enable_cron']){ echo " checked"; } ?>>
						<input type="hidden" value="wps_afr" id="js-action" name="action">
						<input type="hidden" value="update_settings" id="js-ac" name="ac">
					</td>
				</tr>
				
				<tr class="form-field form-required">
					<th scope="row">
						<label for="send_mail_to_admin_after_recovery">Is Send Mail To Admin After Recovery <span class="description">(required)</span></label>
					</th>
					<td>
						<input type="checkbox" id="send_mail_to_admin_after_recovery" name="data[send_mail_to_admin_after_recovery]" value="true" <?php if (isset($data['send_mail_to_admin_after_recovery']) && $data['send_mail_to_admin_after_recovery']){ echo " checked"; } ?>>
					</td>
				</tr>
				
				<tr class="form-field form-required">
					<th scope="row">
						<label for="admin_email">Admin Email<span class="description">(required)</span></label>
					</th>
					<td>
						<input type="email" id="admin_email" name="data[admin_email]" value="<?php if (isset($data['admin_email']) && !empty($data['admin_email'])){ echo $data['admin_email']; } ?>"  required>
					</td>
				</tr>

				<tr class="form-field form-required">
					<th scope="row">
						<label for="cart_url">Cart Url<span class="description">(required)</span></label>
					</th>
					<td>
						<input type="url" id="cart_url" name="data[cart_url]" value="<?php if (isset($data['cart_url']) && !empty($data['cart_url'])){ echo $data['cart_url']; } ?>"  required>
					</td>
				</tr>
				
				<tr class="form-field form-required">
					<th scope="row">
						<label for="cron_time_in_minutes">Cron Time in minutes <span class="description">(required)</span></label>
					</th>
					<td>
						<input type="number" size="6" min="15" max="99999999" value="<?php echo $data['cron_time_in_minutes'];?>" id="cron_time_in_minutes" name="data[cron_time_in_minutes]" required="required" title="Cron Time in minutes">
					</td>
				</tr>
				
				<tr class="form-field form-required">
					<th scope="row">
						<label for="abandoned_time_in_minutes">Abandoned Time in minutes <span class="description">(required)</span></label>
					</th>
					<td>
						<input type="number" size="6" min="15" max="99999999" value="<?php echo $data['abandoned_time_in_minutes'];?>" id="abandoned_time_in_minutes" name="data[abandoned_time_in_minutes]" required="required" title="Abandoned Time in minutes">
					</td>
				</tr>
				
				<tr class="form-field form-required">
					<th scope="row">
						<label for="consider_un_recovered_order_after">Consider Unrecovered Order After <span class="description">(required)</span></label>
					</th>
					<td>
						<input style="float:left; margin-right:2%;width:50%;" type="number" size="6" min="1" max="99999999" value="<?php echo $data['consider_un_recovered_order_after'];?>" id="consider_un_recovered_order_after" name="data[consider_un_recovered_order_after]" required="required" title="Consider Unrecovered Order After">
						<select style="float:left;width:43%;" id="consider_un_recovered_order_after_time_type" name="data[consider_un_recovered_order_after_time_type]" required="required" title="Time Type">	
						<?php foreach($time_types as $key=>$value) { ?>
							<option value="<?php echo $key;?>" <?php if(isset($data['consider_un_recovered_order_after_time_type']) && $data['consider_un_recovered_order_after_time_type'] == $key){?> selected="selected"<?php } ?> ><?php echo $value;?></option>
						<?php } ?>
						</select>
					</td>						
				</tr>			
				
			</tbody>
		</table>
		<p class="submit">
			<button class="wps-btn wps-btn-blue" type="submit">
				<span class="text">Update</span>
			</button>
		</p>
	</form>
</div>