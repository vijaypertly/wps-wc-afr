<?php defined( 'ABSPATH' ) or die(''); ?>
<div style="padding: 0 2%">
<h2>Help</h2>

<p>
    <a href="http://www.wpsupport.io/" target="_blank">Click here</a> for documentation.
</p>
<p>Developed by <a href="http://www.wpsupport.io" target="_blank">www.wpsupport.io</a>.</p>


<h3 style="text-decoration: underline">Cron Details: </h3>
<?php
$serverTimeStamp = time();
$serverTime = date('Y-m-d H:i:s', $serverTimeStamp);
$nextCron = wp_next_scheduled('wps_wc_afr_scheduled_event');
$nextCronStatus = ($nextCron === false)?"Deactivated":"Activated";
$nextCronTime = '-';
$nextCronTimeMins = '';
if($nextCron !== false){
    $nextCronTime = date('Y-m-d H:i:s', $nextCron);
    $nextCronTimeMinsV = (($nextCron-$serverTimeStamp)/60)%60;
    $nextCronTimeSecsV = (($nextCron-$serverTimeStamp)/60*60)%60;
    if($nextCronTimeMinsV>0 || $nextCronTimeSecsV>0){
        $nextCronTimeMins = " - (<b>".$nextCronTimeMinsV." Mins ".$nextCronTimeSecsV." Secs</b> to go)";
    }
}

$lastCronOn = get_option('wps_wc_afr_last_cron_timeon');
$lastCronOnV = '-';

if($lastCronOn!==false){
    $lastCronOnV = date('Y-m-d H:i:s', $lastCronOn);
}
?>
<table class="table" >
    <tbody>

    <tr>
        <td class="pre_code">Status</td>
        <td><?php echo $nextCronStatus; ?></td>
    </tr>

    <tr>
        <td class="pre_code">Current Server Time</td>
        <td><?php echo $serverTime; ?></td>
    </tr>

    <tr>
        <td class="pre_code">Upcoming Cron event</td>
        <td><?php echo $nextCronTime.$nextCronTimeMins; ?> </td>
    </tr>

    <tr>
        <td class="pre_code">Last Cron event performed on</td>
        <td><?php echo $lastCronOnV; ?> </td>
    </tr>
    </tbody>
</table>
<hr>

<h3 style="text-decoration: underline">Short codes to be used on template messages: </h3>

<table class="table" >
    <thead>
        <tr>
            <th>Code</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td class="pre_code">{wps.first_name}</td>
            <td>First name of user</td>
        </tr>

        <tr>
            <td class="pre_code">{wps.last_name}</td>
            <td>Last name of user</td>
        </tr>

        <tr>
            <td class="pre_code">{wps.coupon_details}</td>
            <td>Coupon Message</td>
        </tr>

        <tr>
            <td class="pre_code">{wps.coupon_code}</td>
            <td>Coupon Code</td>
        </tr>

        <tr>
            <td class="pre_code">{wps.cart_url}</td>
            <td>Cart url</td>
        </tr>

    </tbody>
</table>
</div>