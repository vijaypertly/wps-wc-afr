<?php defined( 'ABSPATH' ) or die(''); ?>
<?php
	global $post;
	
	$args = array(
		'posts_per_page'   => -1,
		'orderby'          => 'title',
		'order'            => 'asc',
		'post_type'        => 'shop_coupon',
		'post_status'      => 'publish',
	);  

	if(isset($data['coupon_code']) && !empty($data['coupon_code']) && is_numeric($data['coupon_code'])){
		$sel_args = array(
			'post__in' => array($data['coupon_code']),
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'asc',
			'post_type'        => 'shop_coupon'
		); 
		$selected_coupon = get_posts( $sel_args );
		$args['post__not_in'] = array($data['coupon_code']);
	}

	//$coupons = get_posts( $args );

	//echo "<pre>";print_r($data);echo "</pre>";
	$content = "";
	if(isset($data['template_message'])){
		$content = $data['template_message'];
	}
	$editor_id = "template_message";
	$settings = array('textarea_name' => 'template_message', 'media_buttons' => false);
?>
<?php
	$template_for = array('abandoned_cart'=> 'Abandoned Cart','failed_payment' => 'Failed Payment','cancelled_payment' => 'Cancelled Payment');
	$template_status = array(1 => 'Active',0=> 'Inactive');
	$time_types = array('mins'=> 'Minutes','hours' => 'Hours','days' => 'Days');
$data['template_message'] = html_entity_decode(stripslashes($data['template_message']));
$data['coupon_messages'] = html_entity_decode(stripslashes($data['coupon_messages']));
if(empty($data['template_message'])){
    $data['template_message'] = "  ";
}

if(empty($data['coupon_messages'])){
    $data['coupon_messages'] = "  ";
}
?>
<div class="wrap">
	<?php if(isset($data['id']) && $data['id'] > 0){?> 
		<h1 id="js-add-new-template">Update Template</h1>
		<p>Modify the template</p>
	<?php }else{?> 
		<h1 id="js-add-new-template">Add New Template</h1>
		<p>Create a new template</p>
	<?php }?> 

	<div class="js-error"></div>
	<form id="js-afrcreatetemplate" action="javascript:void(0);" name="afrcreatetemplate" method="post">
		<table class="form-table">
			<tbody>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="template_name">Template Name <span class="description">(required)</span></label>
					</th>
					<td>
						<input type="text" value="<?php echo $data['template_name'];?>" id="template_name" name="template_name" required="required" title="Template Name">
						<input type="hidden" value="<?php echo $data['id'];?>" id="id" name="id">
						<input type="hidden" value="wps_afr" id="js-action" name="action">
						<input type="hidden" value="update_template" id="js-ac" name="ac">
                        <input type="hidden" value="<?php echo self::getNonceFor('update_template'); ?>" id="js-nonce" name="nonce">
					</td>
				</tr>
			<?php if(isset($template_status) && !empty($template_status)) { ?>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="template_status">Template Status <span class="description">(required)</span></label>
					</th>
					<td>
						<select id="template_status" name="template_status" required="required" title="Template Status">							
							<option value="" <?php if(!isset($data['template_status']) || !in_array($data['template_status'],$template_status)){ ?> selected="selected"<?php }?> >Select Option</option>
							<?php foreach($template_status as $key=>$value) { ?>
								<option value="<?php echo $key;?>" <?php if(isset($data['template_status']) && is_numeric($data['template_status']) && $data['template_status'] == $key){?> selected="selected"<?php } ?> ><?php echo $value;?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
			<?php if(isset($template_for) && !empty($template_for)) { ?>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="template_for">Template For <span class="description">(required)</span></label>
					</th>
					<td>
						<select id="template_for" name="template_for" required="required" title="Template For">	
							<option value="">Select Option</option>
							<?php foreach($template_for as $key=>$value) { ?>
								<option value="<?php echo $key;?>" <?php if(isset($data['template_for']) && $data['template_for'] == $key){?> selected="selected"<?php } ?> ><?php echo $value;?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			<?php } ?>
				<?php /* ?>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="send_mail_duration_in_minutes">Send Mail Duration in minutes <span class="description">(required)</span></label>
					</th>
					<td>
						<input type="number" size="6" min="15" max="99999999" value="<?php echo $data['send_mail_duration_in_minutes'];?>" id="send_mail_duration_in_minutes" name="send_mail_duration_in_minutes" required="required" title="Send Mail Duration in minutes">
					</td>
				</tr>
				<?php */ ?>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="send_mail_duration">Send Mail Duration <span class="description">(required)</span></label>
					</th>
					<td>
						<input style="float:left; margin-right:2%;width:50%;" type="number" size="6" min="1" value="<?php echo $data['send_mail_duration'];?>" id="send_mail_duration" name="send_mail_duration" required="required" title="Send Mail Duration">
						<select style="float:left;width:43%;" id="send_mail_duration_time_type" name="send_mail_duration_time_type" required="required" title="Time Type">	
							<option value="">Select Time Type</option>
						<?php foreach($time_types as $key=>$value) { ?>
							<option value="<?php echo $key;?>" <?php if(isset($data['send_mail_duration_time_type']) && $data['send_mail_duration_time_type'] == $key){?> selected="selected"<?php } ?> ><?php echo $value;?></option>
						<?php } ?>
						</select>
					</td>
				</tr>
				 	
				<tr class="form-field form-required">
					<th scope="row">
						<label for="template_subject">Template Subject <span class="description">(required)</span></label>
					</th>
					<td>
						<textarea id="template_subject" name="template_subject" title="Template Subject" required="required"><?php echo $data['template_subject'];?></textarea>
					</td>
				</tr>
				<tr class="form-required">
					<th scope="row">
						<label for="template_message">Template Message <span class="description">(required)</span></label>
					</th>
					<td>
						<?php 
							//wp_editor( html_entity_decode(stripslashes($content)), $editor_id, $settings);
						?>
						<?php  ?>
						<textarea id="template_message" name="template_message" title="Template Message" required="required" style="width: 600px; height: 200px"><?php echo $data['template_message'];?></textarea>
						<?php  ?>
					</td>
				</tr>
				
				<?php /*if((isset($coupons) && !empty($coupons)) || isset($selected_coupon)) {*/ ?>
					<tr class="form-field">
						<th scope="row">
							<label for="coupon_code">Coupon Code</label>
						</th>
						<td>
							<!-- <select id="coupon_code" name="coupon_code" title="Coupon Code">
								<option value="">Select Option</option>
								<?php foreach($selected_coupon as $coupon) { ?>
									<option value="<?php echo $coupon->ID;?>" selected="selected"><?php echo $coupon->post_title;?></option>
								<?php } ?>
								
								<?php foreach($coupons as $coupon) { ?>
									<option value="<?php echo $coupon->ID;?>"><?php echo $coupon->post_title;?></option>
								<?php } ?>
							</select> -->
                            <input type="text" value="<?php echo $data['coupon_code'];?>" id="coupon_code" name="coupon_code" title="Coupon Code">

						</td>
					</tr>
					<tr class="">
						<th scope="row">
							<label for="coupon_messages">Coupon Message</label>
						</th>
						<td>
							<?php 
								if(!isset($data['coupon_messages']) || empty($data['coupon_messages'])){
									$data['coupon_messages'] = "Use the below voucher to avail offer <br/> Coupon Code : {wps.coupon_code}";
								}
								
								$content = "";
								if(isset($data['coupon_messages'])){
									$content = $data['coupon_messages'];
								}
								$editor_id = "coupon_messages";
								$settings = array('textarea_name' => 'coupon_messages', 'media_buttons' => false);
								//wp_editor( html_entity_decode(stripslashes($content)), $editor_id, $settings);
							?>
							<?php  ?>
							<textarea id="coupon_messages" name="coupon_messages" title="Coupon Message" style="width: 600px; height: 200px"><?php echo $data['coupon_messages'];?></textarea>
							<?php  ?>
						</td>
					</tr>
				<?php /*}*/ ?>
				
		</table>
		<p class="submit">
			<button class=" js-add-template wps-btn wps-btn-blue" type="submit">
				<?php if(isset($data['id']) && $data['id'] > 0){?> 
					<span class="text">Update</span>
				<?php }else{?> 
					<span class="text">Add New template</span>
				<?php }?> 
			</button>
			<button class="wps-btn wps-btn-grey js-cancel-template" type="button">
				<span class="text">Cancel</span>
			</button>
		</p>
	</form>
</div>
<?php 
	/*\_WP_Editors::enqueue_scripts();
	print_footer_scripts();
	\_WP_Editors::editor_js();*/
?>
<script>
    var couponMessagesEditor = new nicEditor({fullPanel : true, iconsPath : '<?php echo WPS_WC_AFR_PLUGIN_URL; ?>/assets/nicEditorIcons.gif'}).panelInstance('coupon_messages');
    var templateMessagesEditor = new nicEditor({fullPanel : true, iconsPath : '<?php echo WPS_WC_AFR_PLUGIN_URL; ?>/assets/nicEditorIcons.gif'}).panelInstance('template_message');
</script>