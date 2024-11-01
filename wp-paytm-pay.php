<?php
/*
Plugin Name: Wp Paytm Pay
Version: 1.3.2 
Description: This plugin using visitors to donate via PayTM in either set or custom amounts
Author: FTI Technologies
Author URI: https://www.freelancetoindia.com/
*/

global $paytm_db_version;
$paytm_db_version = '1.0';

require_once(dirname(__FILE__) . '/encdec_paytm.php');

register_activation_hook(__FILE__, 'paytm_activation');
register_deactivation_hook(__FILE__, 'paytm_deactivation');

add_action('init', 'paytm_update_db_check');

if(isset($_GET['donation_msg']) && $_GET['donation_msg'] != ""){ 
    add_action('the_content', 'paytmPayShowMessage'); 
}

function paytm_update_db_check(){
    global $paytm_db_version;
    global $wpdb;

    $installed_ver = get_option("paytm_db_version","1.0");
    $newVersion = '1.1';
    
    if ($installed_ver != $newVersion) {
        paytm_update();
    }
}

function paytm_update(){
    global $paytm_db_version;
    global $wpdb;
    $table_name = $wpdb->prefix . "paytm_donation";
    $newVersion = '1.1';
    $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = $table_name AND column_name = 'pan_no'"  );
    if(empty($row)){
       $wpdb->query("ALTER TABLE $table_name ADD pan_no varchar(255) NOT NULL AFTER amount");
    }
    update_option("paytm_db_version", $newVersion);
}

function paytmPayShowMessage($content){
    return '<div class="box">'.htmlentities(urldecode($_GET['donation_msg'])).'</div>'.$content;
}
		
function paytm_activation(){
	global $wpdb;
    global $paytm_db_version;
    $paytm_db_version = '1.0';

	$settings = paytm_settings_list(); 
	foreach($settings as $setting){
		add_option($setting['name'], $setting['value']);
	}
	
	$table_name = $wpdb->prefix . "paytm_donation";
    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` varchar(255) CHARACTER SET utf8 NOT NULL,
        `name` varchar(255) CHARACTER SET utf8 NOT NULL,
        `phone` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL,
        `address` varchar(255) CHARACTER SET utf8 NOT NULL, 
        `city` varchar(255) CHARACTER SET utf8 NOT NULL,
        `country` varchar(255) CHARACTER SET utf8 NOT NULL,
        `state` varchar(255) CHARACTER SET utf8 NOT NULL,
        `zip` varchar(255) CHARACTER SET utf8 NOT NULL,
        `amount` varchar(255) NOT NULL,
        `comment` text NOT NULL,
        `payment_status` varchar(255) NOT NULL,
        `payment_method` varchar(255) NOT NULL,
        `date` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `id` (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
	
	add_option( 'paytm_db_version', $paytm_db_version);
}

function paytm_deactivation(){
    global $wpdb;
	$settings = paytm_settings_list();
    foreach($settings as $setting){
		delete_option($setting['name']);
	}
    delete_option('paytm_db_version');
}

function paytm_settings_list(){
	$settings = array(
		array(
			'display' => 'Merchant ID',
			'name'    => 'paytm_merchant_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant ID'
		),
		array(
			'display' => 'Merchant Key',
			'name'    => 'paytm_merchant_key',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Merchant key'
		),
		array(
			'display' => 'Website',
			'name'    => 'paytm_website',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Website'
		),
		array(
			'display' => 'Industry Type ID',
			'name'    => 'paytm_industry_type_id', 
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Industry Type ID'
		),
		array(
			'display' => 'Channel ID',
			'name'    => 'paytm_channel_id',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'Channel ID e.g. WEB/WAP'
		),
		array(
			'display' => 'Mode',
			'name'    => 'paytm_mode',
			'value'   => 'TEST',
			'values'  => array('TEST'=>'TEST','LIVE'=>'LIVE'),
			'type'    => 'select',
			'hint'    => 'Change the mode of the payments'
		),
		array(
			'display' => 'Default Amount',
			'name'    => 'paytm_amount',
			'value'   => '100',
			'type'    => 'textbox',
			'hint'    => 'the default donation amount, WITHOUT currency signs -- ie. 100'
		),
		array(
			'display' => 'Default Button/Link Text',
			'name'    => 'paytm_content',
			'value'   => 'Paytm',
			'type'    => 'textbox',
			'hint'    => 'the default text to be used for buttons or links if none is provided'
		),
		array(
			'display' => 'ThankYou Page',
			'name'    => 'paytm_thanks_page_url',
			'value'   => '',
			'type'    => 'textbox',
			'hint'    => 'redirect on this page after successful payment, leave blank if redirect on same page'
		)
	);
	return $settings;
}


if(is_admin()){
	add_action( 'admin_menu', 'paytm_admin_menu' );
	add_action( 'admin_init', 'paytm_register_settings' ); 
}

function paytm_admin_menu(){
	add_menu_page('Paytm Settings', 'Paytm Settings', 'manage_options', 'paytm_options_page', 'paytm_options_page', plugin_dir_url( __FILE__ ) . 'paytm-wallet.png');
	add_submenu_page('paytm_options_page','Paytm Paymet Details','Paytm Paymet Details','manage_options','wp_paytm_donation','wp_paytm_donation');
	?>
	<style type="text/css">
		.toplevel_page_paytm_options_page .wp-menu-image img{ max-width:20px; padding-top:5px !important; }
	</style> 
	<?php
}

function paytm_options_page(){
	echo '<div class="wrap">
		<h2>Paytm Configurations</h2>
			<form method="post" action="options.php" style="float:left; clear:none;">';
				wp_nonce_field('update-options');
				echo '<table class="form-table">';
					$settings = paytm_settings_list();
					foreach($settings as $setting){
						echo '<tr><th scope="row">'.$setting['display'].'</th><td>';
						if($setting['type']=='radio'){
							echo $setting['yes'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="1" ';
							if (get_option($setting['name'])==1) { echo 'checked="checked" />'; } else { echo ' />'; }
							echo $setting['no'].' <input type="'.$setting['type'].'" name="'.$setting['name'].'" value="0" ';
							if (get_option($setting['name'])==0) { echo 'checked="checked" />'; } else { echo ' />'; }
						} elseif($setting['type']=='select'){
							$values = $setting['values'];
							echo '<select name="'.$setting['name'].'">';
							foreach($values as $value => $name){
								echo '<option value="'.$value.'" ';
								if (get_option($setting['name']) == $value) { echo ' selected="selected" '; }
								echo '>'.$name.'</option>';
							}
							echo '</select>';
						} else { echo '<input type="'.$setting['type'].'" name="'.$setting['name'].'" value="'.get_option($setting['name']).'" style="width: 400px;" />'; }
						echo ' (<em>'.$setting['hint'].'</em>)</td></tr>';
					}
					echo '<tr><th style="text-align:center;"></th><td><input type="submit" class="button-primary" value="Save Changes" />';
					echo '<input type="hidden" name="action" value="update" />
					<input type="hidden" name="page_options" value="';
					foreach($settings as $setting){
						echo $setting['name'].',';
					}
					echo '" /></td></tr>
				</table>
			</form>';
	echo '</div>';
}

function wp_paytm_donation(){
	require_once(dirname(__FILE__) . '/wp-paytm-pay-listings.php');
}

function paytm_register_settings(){
	$settings = paytm_settings_list();
	foreach ($settings as $setting){
		register_setting($setting['name'], $setting['value']);
	}
}

add_shortcode( 'paytmpay', 'paytm_donate_button' );

function paytm_donate_button(){
	if(isset($_POST['ORDERID']) && isset($_POST['RESPCODE'])){
		require_once(dirname(__FILE__) . '/wp-paytm-response.php');
	} else {
		if(!isset($_GET['donation_msg'])){
			echo require_once(dirname(__FILE__) . '/wp-paytm-form.php');
		}
	}	
}
?>