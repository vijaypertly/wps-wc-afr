<?php defined( 'ABSPATH' ) or die(''); ?>
<div class="the-add-template" style="clear:both;float:right; margin:10px 3px;">
	<a class="wps_add_template btn btn-primary">Add Template</a>
</div>
<div class="the-template-content" style="clear:both;display:table;margin:10px 3px;">
	<?php echo self::getHtml('_tabsection_templates_ajax'); ?>
</div>