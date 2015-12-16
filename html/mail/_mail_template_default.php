<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body  class="body" style="padding:0; margin:0; display:block; background:#FFF; -webkit-text-size-adjust:none;" bgcolor="#eeebeb">

    <table align="center" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" valign="top" style="background:#e6e6e6; text-align:center; padding: 2%" width="100%">

            <table align="center" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td align="center" valign="top" style="font-size:24px; background:#f0b20a;color: #ffffff; text-align:center;" width="100%">
                        <p class="site_title"><a href="__SITE_URL__" style="text-decoration: none; color: #FFFFFF">__SITE_TITLE__</a></p>
                    </td>
                </tr>
                <tr>
                    <td style="background: #f0b20a;  font-size:20px; text-align:center; padding: 0 75px; color: #FFFFFF;">
                        <p class="site_description">__SITE_DESCRIPTION__</p>
                    </td>
                </tr>
                <tr>
                    <td style="font-size:14px; background: #FFFFFF; text-align:left; padding: 5% 10%;">
                        <p class="site_message">__MESSAGE__</p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#363636;color:#f0f0f0; font-size: 14px; text-align:center; padding: 5% 2%;">
                        &#169; <?php echo date('Y'); ?> __SITE_TITLE__
                    </td>
                </tr>
            </table>
            </td>
        </tr>
    </table>
    <img src="<?php echo admin_url('admin-ajax.php'); ?>?action=wpsafr_ajx&wpsac=rm&mid=__MID__" width="1" height="1" />
</body>
</html>


