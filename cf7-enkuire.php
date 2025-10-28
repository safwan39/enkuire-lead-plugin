<?php
/**
 * @package Enkuire Lead
 * @version 1.1.0
 */
/*
Plugin Name: Enkuire Lead
Description: Save Contact form 7 to enkuire
Author: Caspian Digital Solution
Author URI: https://caspiands.com/
Version: 1.1.0
Tested up to: 6.9
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: cf7-enkuire
*/

/*
 * curl_file_create functions replacement
 */
if (!function_exists('curl_file_create')){
    function curl_file_create($filename,$mimetype='',$postname=''){
        return "@$filename;filename=".($postname ?: basename($filename)).($mimetype ? ";type=$mimetype" : '');
    }
}

register_deactivation_hook( __FILE__, 'cds_deactivated');
function cds_deactivated(){
    delete_option('enkuire');
}



function cds_process_lead( $form_tag ) {
    $submission   = WPCF7_Submission::get_instance();
    $contact_form = $submission->get_contact_form();
    $options = get_option('enkuire');
    $data = $submission->get_posted_data();
    $files = $submission->uploaded_files();

    $data['lead_campaign'] = $contact_form->title();
    $data['lead_device'] = $_SERVER['HTTP_USER_AGENT'];
    $data['lead_website'] = $_SERVER['SERVER_ADDR'];
    $data['lead_location_ip'] = cds_getRealIp();

    if(!empty($options['group_id']))
        $data['lead_group'] = $options['group_id'];

    $data = cds_setFieldValue($data);
    // foreach($_FILES as $k=>$f){ $data[$k]=curl_file_create($files[$k],$f['type'],$f['name']);} //handle file upload ::special handle for contact form 7 $this->uploaded_files[$k] it should be $f['tmp_name']
	$options = get_option('enkuire') ?: ['submit_url'=> []];
	$urls = $options['submit_url'] ?: [];
	foreach($urls as $url){
		$url = trim($url);
		if($url) wp_remote_post(rtrim($url, '/').'/GetLead.php', ['body'=> $data]);	
	}
}

add_action( 'wpcf7_before_send_mail', 'cds_process_lead');


function cds_getRealIp(){
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) return $_SERVER["HTTP_CLIENT_IP"];
    elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) return $_SERVER["HTTP_X_FORWARDED_FOR"];
    else return $_SERVER["REMOTE_ADDR"];
}

function cds_setFieldValue($data){
    $cols = [ '\w+name$' => 'lead_name', 'email'=> 'lead_email', '(contact|mobile|phone)([-_]?number)?$'=> 'lead_phone', '^(utm|utm_source)$' => 'lead_utm']; 
    foreach($cols as $regex => $col){
        foreach($data as $k => $v){
            if(preg_match('/'. $regex .'/i', $k)){
                $data[$col] = $v;
                break;
            }
        }
        if(!isset($data[$col])) $data[$col] = '';
    }
    return $data;
}

//setting page
function cds_add_settings_page() {
    add_options_page( 'Enkuire Setting', 'Enkuire', 'manage_options', 'enkuire', 'cds_render_plugin_settings_page' );
}
add_action( 'admin_menu', 'cds_add_settings_page' );

function cds_render_plugin_settings_page() {
    ?>
    <h2>Enkuire Setting</h2>
    <form action="options.php" method="post">
        <?php 
        settings_fields( 'enkuire' );
        do_settings_sections( 'enkuire' ); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save', 'cf7-enkuire'); ?>" />
    </form>
    <?php
}

function cds_register_settings() {
    register_setting(
        'enkuire', 
        'enkuire', 
        array(
            'sanitize_callback' => 'cds_sanitize_options',
            'default' => array('group_id' => '', 'submit_url' => array(''))
        )
    );
    add_settings_section('enkuire_section', '', '__return_false', 'enkuire' );

    add_settings_field( 'enk_group_id', 'Organization Id', 'cds_group_id', 'enkuire', 'enkuire_section' );
	add_settings_field( 'enk_submit_url', 'Portal URLs <button id="add-url">Add URL</button>', 'cds_submit_url', 'enkuire', 'enkuire_section' );
}
add_action( 'admin_init', 'cds_register_settings' );


function cds_group_id() {
    $options = get_option('enkuire') ?: ['group_id'=> ''];
    echo "<input id='enk_group_id' name='enkuire[group_id]' type='number' value='" . esc_attr( $options['group_id'] ) . "' />";
}

function cds_submit_url() {
    $options = get_option('enkuire') ?: ['submit_url'=> []];
	$urls = $options['submit_url'] ?: [''];
	foreach($urls as $url){
    	echo "<div class='url_row'>
			<input name='enkuire[submit_url][]' type='text' value='" . esc_attr( $url ) . "' />
			 <button style='display: ".(count($urls)>1 ? 'block' : 'none') ."'>remove</button>
		</div>";
	}
	echo "<style>.url_row{display:flex;margin-bottom:5px}.url_row input{width:500px}</style><script>
		jQuery('#add-url').click(function(e){
			e.preventDefault();
			var clone = jQuery('.url_row').eq(0).clone();
			jQuery('input', clone).val('');
			jQuery('.url_row').parent().append(clone);
			jQuery('.url_row button').css('display', jQuery('.url_row').length>1? 'block' : 'none' );
		});
		jQuery(document).on('click', '.url_row button', function(e){
			e.preventDefault();
			jQuery(this).closest('.url_row').remove();
			jQuery('.url_row button').css('display', jQuery('.url_row').length>1? 'block' : 'none' );
		})
	</script>";
}

/**
 * Sanitize the options before saving to database
 *
 * @param array $input The input array to sanitize
 * @return array Sanitized input array
 */
function cds_sanitize_options($input) {
    $sanitized_input = array();
    
    // Sanitize group_id (should be a positive integer)
    if (isset($input['group_id'])) {
        $sanitized_input['group_id'] = absint($input['group_id']);
    } else {
        $sanitized_input['group_id'] = '';
    }
    
    // Sanitize submit_url array (should be valid URLs)
    if (isset($input['submit_url']) && is_array($input['submit_url'])) {
        $sanitized_input['submit_url'] = array();
        foreach ($input['submit_url'] as $url) {
            $clean_url = esc_url_raw(trim($url));
            if (!empty($clean_url)) {
                $sanitized_input['submit_url'][] = $clean_url;
            }
        }
    } else {
        $sanitized_input['submit_url'] = array('');
    }
    
    return $sanitized_input;
}

// Add referrer URL and search params to all forms
add_action('wp_footer', function () {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('form');
        const url = window.location.href;
        const searchParams = new URLSearchParams(window.location.search);

        forms.forEach(form => {
            // Add full referrer URL
            const refInput = document.createElement('input');
            refInput.type = 'hidden';
            refInput.name = 'ref_url';
            refInput.value = url;
            form.appendChild(refInput);

            // Add each search parameter as its own hidden input
            searchParams.forEach((value, key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            });
        });
    });
    </script>
    <?php
});