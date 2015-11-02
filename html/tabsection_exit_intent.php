<?php defined( 'ABSPATH' ) or die(''); ?>
<?php
$opts = get_option('wps_wc_ei_settings'); 				
?>

<table style="margin-left:30px;" class="form-table">
    <tbody>
    <tr>
        <th scope="row"><label for="">Exit Intent Modal Title : </label></th>
        <td><input type="text" name="wps-ei-title" value="<?php echo esc_html($opts['wps-ei-title']); ?>" /></td>
    </tr>		
    <tr>
        <td colspan="2">
            <p class="submit"><input type="submit" class="button-primary" name="ct_admin_options" value="<?php esc_attr_e('Save Changes') ?>" /></p>
        </td>
    </tr>         				               				
    </tbody>
</table>

