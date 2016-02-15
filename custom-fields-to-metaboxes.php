<?php
/*
Plugin Name: Custom Fields to Metaboxes
Plugin URI:
Description: Migrate custom fields to metabox fields.
Version: 0.1.0
Author: Erik Mitchell
Author URI: http://erikmitchell.net
Text Domain: cftmb
Domain Path: /languages
License: GPL2
*/

/**
 * CustomFieldstoMetabox class.
 */
class CustomFieldstoMetabox {

	public $options=array();
	public $wp_option_name='custom_fields_to_metabox_options';
	public $admin_slug='custom-fields-to-metabox';

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->setup_options();

		add_action('admin_init',array($this,'update_options'));
		add_action('admin_enqueue_scripts',array($this,'admin_scripts_styles'));
		add_action('admin_menu',array($this,'admin_menu'));
		add_action('wp_ajax_get_fields_to_map',array($this,'ajax_get_fields_to_map'));
		add_action('wp_ajax_get_metaboxes',array($this,'ajax_get_metaboxes'));
		add_action('wp_ajax_process_custom_fields_to_metabox',array($this,'ajax_process_custom_fields_to_metabox'));
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_management_page('Custom Fields to Metabox','Cust. Fields to Meta','manage_options','custom-fields-to-metabox',array($this,'admin_page'));
	}

	/**
	 * admin_scripts_styles function.
	 *
	 * @access public
	 * @param mixed $hook
	 * @return void
	 */
	public function admin_scripts_styles($hook) {
		if ($hook!='tools_page_custom-fields-to-metabox')
			return false;

		wp_enqueue_script('cftmb-admin-scripts',plugins_url('/js/admin.js',__FILE__),array('jquery'));

		wp_localize_script('cftmb-admin-scripts','options',$this->options);

		wp_enqueue_style('cftmb-admin-style',plugins_url('/css/admin.css',__FILE__));
	}

	/**
	 * admin_page function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_page() {
		?>
		<div class="wrap cftmb-admin-page">
			<h1>Custom Fields to Metabox</h1>

			<?php if ($this->options['debug']) : ?>
				<strong>DEBUG ON</strong>
			<?php endif; ?>

			<div id="cftmb-admin-notices"></div>

			<p>
				This plugin will allow you to match the custom fields of a post type to metabox fields. It will allow you to match the fields and then
				will automatically convert them.<br />
				<strong>Steps:</strong>

				<ol class="cftmb-steps">
					<li>Select a post type (below).</li>
					<li>Choose a metabox.</li>
					<li>Map custom fields to metabox fields.</li>
					<li>Process them and let the magic happen</li>
				</ol>
			</p>

			<form method="post" action="" id="step-1" class="step active">
				<h2>Select a Post Type</h2>

				<table class="form-table">
					<tr>
						<th scope="row">Post Type</th>
						<td id="get-post-type">
							<fieldset>
								<legend class="screen-reader-text"><span>Post Type</span></legend>
								<?php echo $this->get_post_types_list(); ?>
							</fieldset>
						</td>
					</tr>
				</table>

				<p class="submit"><input type="button" id="step_1_submit" class="stepbtn button button-secondary" value="Continue"></p>
			</form>

			<form method="post" action="" id="step-2" class="step">
				<h2>Select a Metabox</h2>
				<table class="form-table">
					<tr>
						<th scope="row">Metaboxes</th>
						<td id="get-metabox">
							<fieldset>
								<legend class="screen-reader-text"><span>Metaboxes</span></legend>
								<div id="metabox-list"></div>
							</fieldset>
						</td>
					</tr>
				</table>

				<p class="submit"><input type="button" name="step_2_submit" id="step_2_submit" class="stepbtn button button-secondary" value="Continue"></p>
			</form>

			<form method="post" action="" id="step-3" class="step">
				<h2>Map Fields</h2>

				<?php wp_nonce_field('process','cftmb_admin_page'); ?>

				<table class="form-table"></table>

				<div class="cftmb-db-backup-warning">
					<strong>Important:</strong><br />
					Clicking the process button will make changes to your database.<br />
					Before updating, please <a href="https://codex.wordpress.org/WordPress_Backups">back up your database and files</a>.
				</div>

				<p class="submit"><input type="button" name="step_3_submit" id="step_3_submit" class="stepbtn button button-primary" value="Process"></p>
			</form>

			<form method="post" action="" class="admin-options active">
				<h2>Admin Options</h2>

				<?php wp_nonce_field('update_options','cftmb_admin_page'); ?>
				<input type="hidden" name="options[foo]" value="bar">

				<table class="form-table">
					<tr>
						<th scope="row">Debug</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span>Debug</span></legend>
								<label for="debug">
									<input name="options[debug]" type="checkbox" id="debug" value="1" <?php checked($this->options['debug'],1); ?>>
									Enable Debug (advanced users only)
								</label>
							</fieldset>
						</td>
					</tr>
				</table>

				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
			</form>

			<div id="debug_notices" class="">
				<h3>Debug Notices</h3>
				<div id="notices">
					<div id="clear_notices"><a href="#">Clear</a></div>
					<div class="notices-wrap"></div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * get_post_types_list function.
	 *
	 * @access public
	 * @return void
	 */
	public function get_post_types_list() {
		$html=null;
		$args=array(
			'public' => true
		);
		$post_types=get_post_types($args);

		if (!count($post_types))
			return false;

		foreach ($post_types as $post_type) :
			$html.='<label title="'.$post_type.'">';
				$html.='<input type="radio" name="post_type" id="'.$post_type.'" value="'.$post_type.'"> '.$post_type;
			$html.='</label><br />';
		endforeach;

		return $html;
	}

	/**
	 * ajax_get_metaboxes function.
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_get_metaboxes() {
		$post_type=false;
		$data=array();

		if (isset($_POST['post_type']))
			$post_type=$_POST['post_type'];

		$data['post_type_html']='<input type="hidden" name="post_type" id="post_type" value="'.$post_type.'">';
		$data['metabox_list_html']=$this->get_metabox_list($post_type);

		echo json_encode($data);

		wp_die();
	}

	/**
	 * ajax_get_fields_to_map function.
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_get_fields_to_map() {
		$table_html=null;
		$hidden_fields=null;
		$data=array();
		$metabox_fields=$this->get_metabox_fields($_POST['metabox_id']);
		$custom_fields=$this->get_all_custom_field_keys($_POST['post_type']);

		if (!count($metabox_fields) || !count($custom_fields))
			return false;

		$metabox_fields_dropdown_html=$this->get_fields_dropdown($metabox_fields,'metabox_fields[]');

		$table_html.='<tr>';
			$table_html.='<th scope="row">Custom Field</th>';
			$table_html.='<th scope="row">Metabox Field</th>';
		$table_html.='</tr>';

		foreach ($custom_fields as $custom_field) :
			$table_html.='<tr>';
				$table_html.='<td scope="row"><input type="text" name="custom_fields[]" class="regular-text" value="'.$custom_field.'" readonly ></td>';
				$table_html.='<td><label for="">'.$metabox_fields_dropdown_html.'</label></td>';
			$table_html.='</tr>';
		endforeach;

		if (isset($_POST['post_type']))
			$hidden_fields.='<input type="hidden" name="post_type" id="post_type" value="'.$_POST['post_type'].'" >';

		if (isset($_POST['metabox_id']))
			$hidden_fields.='<input type="hidden" name="metabox_id" id="metabox_id" value="'.$_POST['metabox_id'].'" >';

		$data['table_html']=$table_html;
		$data['hidden_fields']=$hidden_fields;

		echo json_encode($data);

		wp_die();
	}

	/**
	 * get_metabox_list function.
	 *
	 * @access public
	 * @param bool $post_type (default: false)
	 * @return void
	 */
	public function get_metabox_list($post_type=false) {
		$all_metaboxes=$this->clean_global_metaboxes();
		$metabox_arr=array();
		$metabox_list=array();
		$html=null;

		// get an array of [post_types][id][name] //
		foreach ($all_metaboxes as $_post_type => $metaboxes) :
			foreach ($metaboxes as $id => $metabox) :
				$metabox_arr[$_post_type][$id]=$metabox[$id]['title'];
			endforeach;
		endforeach;

		// check for passed post type //
		if ($post_type and isset($metabox_arr[$post_type])) :
			$metabox_list=$metabox_arr[$post_type];
		else :
			foreach ($metabox_arr as $arr) :
				foreach ($arr as $id => $mb) :
					$metabox_list[$id]=$mb;
				endforeach;
			endforeach;
		endif;

		// build out list //
		foreach ($metabox_list as $id => $name) :
			$html.='<label title="'.$id.'">';
				$html.='<input type="radio" name="metabox" id="'.$id.'" value="'.$id.'"> '.$name;
			$html.='</label><br />';
		endforeach;

		return $html;
	}

	/**
	 * clean_global_metaboxes function.
	 *
	 * @access public
	 * @return void
	 */
	public function clean_global_metaboxes() {
		global $wp_meta_boxes;

		$metaboxes=array();

		foreach ($wp_meta_boxes as $post_type => $context) :
			foreach ($context as $priority) :
				foreach ($priority as $metabox) :
					foreach ($metabox as $id => $mb) :
						$metaboxes[$post_type][$id]=$metabox;
					endforeach;
				endforeach;
			endforeach;
		endforeach;

		return $metaboxes;
	}

	/**
	 * get_metabox_fields function.
	 *
	 * @access public
	 * @param bool $metabox_id (default: false)
	 * @return void
	 */
	public function get_metabox_fields($metabox_id=false) {
		if (!$metabox_id)
			return false;

		$all_metaboxes=$this->clean_global_metaboxes();

		foreach ($all_metaboxes as $post_type => $metaboxes) :
			foreach ($metaboxes as $id => $metabox) :
				if ($metabox_id==$id)
					return $metabox[$id]['callback'];
			endforeach;
		endforeach;

		return false;
	}

	/**
	 * get_all_custom_field_keys function.
	 *
	 * @access public
	 * @param string $post_type (default: 'post')
	 * @return void
	 */
	public function get_all_custom_field_keys($post_type='post') {
		$custom_field_keys=array();
		$posts=get_posts(array(
			'posts_per_page' => -1,
			'post_type' => $post_type,
			'post_status' => 'any'
		));
		$field_keys_ignore=array('_edit_last','_edit_lock');

		if (!count($posts))
			return false;

		// get all custom keys from all posts //
		foreach ($posts as $post)	:
			$custom_keys=get_post_custom_keys($post->ID);

			// cehck that we have custom keys //
			if (empty($custom_keys) || !$custom_keys)
				continue;

			foreach ($custom_keys as $key) :
				$custom_field_keys[]=$key;
			endforeach;
		endforeach;

		$custom_field_keys=array_unique($custom_field_keys); // get distinctkeys
		$custom_field_keys=array_values($custom_field_keys); // reset keys

		// remove our ignore keys //
		foreach ($custom_field_keys as $id => $key) :
			if (in_array($key,$field_keys_ignore))
				unset($custom_field_keys[$id]);
		endforeach;

		$custom_field_keys=array_values($custom_field_keys); // reset keys

		return $custom_field_keys;
	}

	/**
	 * get_fields_dropdown function.
	 *
	 * @access public
	 * @param array $fields (default: array())
	 * @param string $name (default: 'cftmb_name')
	 * @return void
	 */
	public function get_fields_dropdown($fields=array(),$name='cftmb_name',$keys=false) {
		if (empty($fields))
			return false;

		$html=null;

		$html.='<select name="'.$name.'">';
			$html.='<option value="empty">Select One</option>';
			foreach ($fields as $field) :
				$html.='<option value="'.$field.'">'.$field.'</option>';
			endforeach;
		$html.='</select>';

		return $html;
	}

	/**
	 * ajax_process_custom_fields_to_metabox function.
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_process_custom_fields_to_metabox() {
		$form_values=array();
		parse_str($_POST['form'],$form_values);

		// setus up an array with [custom field key] => metbaox field key
		$fields_map=array_combine($form_values['custom_fields'],$form_values['metabox_fields']);

		// remove any non mapped fields //
		foreach ($fields_map as $custom_key => $metabox_key) :
			if ($metabox_key=='empty') :
				unset($fields_map[$custom_key]);
			endif;
		endforeach;

		$return=$this->move_custom_fields_to_metabox($fields_map,$form_values['post_type']);

		echo json_encode($return);

		wp_die();
	}

	/**
	 * move_custom_fields_to_metabox function.
	 *
	 * @access protected
	 * @param array $fields_map (default: array())
	 * @param string $post_type (default: 'posts')
	 * @return void
	 */
	protected function move_custom_fields_to_metabox($fields_map=array(),$post_type='posts') {
		$debug_notices=array();
		$posts=get_posts(array(
			'posts_per_page' => -1,
			'post_type' => $post_type,
			'post_status' => 'any'
		));

		if (!count($posts) || empty($fields_map))
			return false;

		foreach ($posts as $post) :
			$result=$this->process_post($post->ID,$fields_map);

			if (!empty($result))
				$debug_notices[]=$result;
		endforeach;

		if ($this->options['debug']) :
			return $debug_notices;
		else :
			return '<div class="updated">Custom fields migrated.</div>';
		endif;
	}

	/**
	 * process_post function.
	 *
	 * @access protected
	 * @param bool $post_id (default: false)
	 * @param array $fields_map (default: array())
	 * @return void
	 */
	protected function process_post($post_id=false,$fields_map=array()) {
		if (!$post_id || empty($fields_map))
			return false;

		$debug_notices=array();

		// add to metabox and remove from custom fields
		foreach ($fields_map as $custom_key => $meta_key) :
			$update_metabox=false;
			$delete_custom_field=false;
			$meta_value=get_post_meta($post_id,$custom_key,true);

			if ($meta_value && $meta_value!='') :
				$update_metabox=update_post_meta($post_id,$meta_key,$meta_value); // update our metabox field
				$delete_custom_field=delete_post_meta($post_id,$custom_key); // remove custom field

				if ($this->options['debug']) :
					if ($update_metabox) :
						$debug_notices[]='<div class="cftmb-debug-updated">Post: '.$post_id.' - "'.$meta_key.'" added to metabox.</div>';
					else :
						$debug_notices[]='<div class="cftmb-debug-error">Post: '.$post_id.' - "'.$meta_key.'" failed to add metabox.</div>';
					endif;

					if ($delete_custom_field) :
						$debug_notices[]='<div class="cftmb-debug-updated">Post: '.$post_id.' - "'.$custom_key.'" deleted.</div>';
					else :
						$debug_notices[]='<div class="cftmb-debug-error">Post: '.$post_id.' - "'.$custom_key.'" unable to be deleted.</div>';
					endif;
				endif;
			endif;
		endforeach;

		return $debug_notices;
	}

	/**
	 * update_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function update_options() {
		if (!isset($_POST['cftmb_admin_page']) || !wp_verify_nonce($_POST['cftmb_admin_page'],'update_options'))
			return false;

		if (!isset($_POST['options']))
			return false;

		// for our checkboxes //
		if (!isset($_POST['options']['debug']) || $_POST['options']['debug']=='')
			$_POST['options']['debug']=0;

		$this->options=wp_parse_args($_POST['options'],$this->options);

		update_option($this->wp_option_name,$this->options);
	}

	/**
	 * setup_options function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function setup_options() {
		$options=get_option($this->wp_option_name,array());
		$default_options=array(
			'debug' => 0
		);

		$this->options=wp_parse_args($options,$default_options);
	}

}

new CustomFieldstoMetabox();
?>