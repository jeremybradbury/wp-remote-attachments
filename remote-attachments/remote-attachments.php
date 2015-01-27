<?php
/*
 Plugin Name: Remote Attachments
 Plugin URI: https://github.com/jeremybradbury/wp-remote-attachments
 Description: Allows local/dev/production to all use the same production "attachments" aka: uploads/images. Point your local machine, dev and test servers at production uploads folder. You don't need to deal with broken links or copying images to other servers. No need to sync 'uploads' to the test server, just look in one place for ALL images. Since the single setting is simply a FQ path to a public upload directory, this can stay activated AND use the same settings as Dev/Test.
 Version: 0.0.1
 Author: <a href="http://github.com/jeremybradbury">Jeremy Bradbury</a>
 Author URI: http://github.com/jeremybradbury
 */
/**
 * @package Remote Attachments
 * @encoding UTF-8
 * @author Jeremy Bradbury <jeremybradbury>
 * @link http://github.com/jeremybradbury
 * @license http://www.gnu.org/licenses/
 */
class RemoteAttachments
{
	const textdomain = 'remote-attachment';
	const plugin_name = 'Remote Attachment';
	const opt_primary = 'remote_attachment_options';
	const version = '0.0.1';
	private static $remote_url = '';
	private static $remote_baseurl = '';
	private static $local_url = '';
	private static $local_baseurl = '';
	public function __construct()
	{
		self::init();
		//menu
		add_action('admin_menu', array(__CLASS__, 'plugin_menu'));
		//frontend filter,filter on image only
		add_filter('wp_get_attachment_url', array(__CLASS__, 'replace_baseurl'), -999);
	}
	private static function update_options()
	{
		$value = self::get_default_opts();
		$keys = array_keys($value);
		foreach ($keys as $key)
		{
			if (!empty($_POST[$key]))
			{
				$value[$key] = addslashes(trim($_POST[$key]));
			}
		}
		$value['remote_baseurl'] = rtrim($value['remote_baseurl'], '/');
		if (update_option(self::opt_primary, $value))
			return TRUE;
		else
			return FALSE;
	}
	/**
	 * get the default options
	 * @static
	 * @return array
	 */
	private static function get_default_opts()
	{
		return array(
			'remote_baseurl' => self::$remote_baseurl,
		);
	}
	/**
	 * init
	 * @static
	 * @return void
	 */
	public static function init()
	{
		register_activation_hook(__FILE__, array(__CLASS__, 'my_activation'));
		register_deactivation_hook(__FILE__, array(__CLASS__, 'my_deactivation'));
		$opts = get_option(self::opt_primary);
		$opts['remote_baseurl'] = rtrim($opts['remote_baseurl'], '/');
		$upload_dir = wp_upload_dir();
		//be aware of / in the end
		self::$local_baseurl = $upload_dir['baseurl'];
		self::$local_url = $upload_dir['url'];
		self::$remote_baseurl = $opts['remote_baseurl'];
		self::$remote_url = self::$remote_baseurl;
	}
	/**
	 * do the stuff once the plugin is installed
	 * @static
	 * @return void
	 */
	public static function my_activation()
	{
		$opt_primary = self::get_default_opts();
		add_option(self::opt_primary, $opt_primary);
	}
	/**
	 * do cleaning stuff when the plugin is deactivated.
	 * @static
	 * @return void
	 */
	public static function my_deactivation()
	{
		//delete_option(self::opt_primary);
	}
	private static function get_opt($key, $defaut='')
	{
		$opts = get_option(self::opt_primary);
		return isset($opts[$key]) ? $opts[$key] : $defaut;
	}
	/**
	 * the hook is in function get_attachment_link()
	 * @static
	 * @param $html
	 * @return mixed
	 */
	public static function replace_attachurl($html)
	{
		$html = str_replace(self::$local_url, self::$remote_url, $html);
		return $html;
	}
	/**
	 * the hook is in function media_send_to_editor
	 * @static
	 * @param $html
	 * @return mixed
	 */
	public static function replace_baseurl($html)
	{
		$html = str_replace(self::$local_baseurl, self::$remote_baseurl, $html);
		return $html;
	}
	/**
	 * add menu page
	 * @see http://codex.wordpress.org/Function_Reference/add_options_page
	 * @static
	 * @return void
	 */
	public static function plugin_menu()
	{
		$identifier = md5(__FILE__);
		$option_page = add_options_page(__('Attachment Options', self::textdomain), __('Remote Attachments', self::textdomain), 'manage_options', $identifier, array(__CLASS__, 'plugin_options')
		);
	}
	public static function show_message($message, $type = 'e')
	{
		if (empty($message))
			return;
		$font_color = 'e' == $type ? '#FF0000' : '#4e9a06';
		$html = '<!-- Last Action --><div class="updated fade"><p>';
		$html .= "<span style='color:{$font_color};'>" . $message . '</span>';
		$html .= '</p></div>';
		echo $html;
	}
	/**
	 * option page
	 * @static
	 * @return void
	 */
	public static function plugin_options()
	{
		$msg = '';
		$error = '';

		//update options
		if (isset($_POST['submit']))
		{
			if (self::update_options())
			{
				$msg = __('Options updated.', self::textdomain);
			}
			else
			{
				$error = __('Nothing changed.', self::textdomain);
			}
		}

		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2> <?php _e('Remote Attachment Options', self::textdomain) ?></h2>
			<?php
			self::show_message($msg, 'm');
			self::show_message($error, 'e');
			?>
			<form name="form1" method="post"
				  action="<?php echo admin_url('options-general.php?page=' . md5(__FILE__)); ?>">
				<table width="100%" cellpadding="5" class="form-table">
					<tr valign="top">
						<th scope="row"><label for="remote_baseurl"><?php _e('Production uploads folder', self::textdomain) ?>
								:</label></th>
						<td>
							<input name="remote_baseurl" type="text" class="regular-text" size="60" id="remote_baseurl"
								   value="<?php echo self::get_opt('remote_baseurl'); ?>"/>
							<span class="description"><?php _e('dev/local/prod can all load production images <strong>http://www.your-domain.com/wp-content/uploads</strong>.', self::textdomain); ?></span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" name="submit"
						   value="<?php _e('Save Options', self::textdomain); ?> &raquo;"/>
				</p>
			</form>
		</div>
		<?php
	}
}
new RemoteAttachments();