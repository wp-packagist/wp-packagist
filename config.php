<?php

return array(
	/**
	 * Get path cache folder
	 */
	'cache_dir'     => WP_CLI_PACKAGIST_CACHE_PATH,

	/**
	 * Online document url
	 */
	'docs'          => 'https://github.com/wp-packagist/wp-packagist/wiki/',

	/**
	 * WordPress API
	 */
	'wordpress_api' => array(

		# Wordpress translations List
		'translations' => "https://api.wordpress.org/translations/core/1.0/",

		# WordPress Version List API
		'version'      => "http://api.wordpress.org/core/stable-check/1.0/",

		# WordPress Plugins Directory
		'plugins'      => array(
			'plugin_data' => 'https://api.wordpress.org/plugins/info/1.0/[slug].json',
			'cache_dir'   => '_plugins',
			'file_name'   => "[slug].json",
			'age'         => 1 #Hour
		),

		# WordPress Themes Directory
		'themes'       => array(
			'themes_data'         => 'https://api.wordpress.org/themes/info/1.1/?action=query_themes&request[theme]=[slug]',
			'themes_version_list' => 'https://themes.svn.wordpress.org/[slug]/',
			'cache_dir'           => '_themes',
			'file_name'           => "[slug].json",
			'age'                 => 1 //Hour
		),

		# plugin or theme slug preg (https://developer.wordpress.org/themes/functionality/internationalization/)
		'slug'         => '/[^a-zA-Z0-9-_]/',

		# List Of cli Log
		'log'          => array(
			'connect'           => 'Error connecting to ' . \WP_CLI_Helper::color( "WordPress.org API", "Y" ) . '. Please check your internet connection and try again.',
			'not_found'         => "The '" . \WP_CLI_Helper::color( "[name]", "Y" ) . "' not found in WordPress.org [type] directory.",
			'not_available_ver' => "The '" . \WP_CLI_Helper::color( "[name]", "Y" ) . "' [type] is not available in [ver] version. show available versions '" . \WP_CLI_Helper::color( 'wp pack help', 'Y' ) . "'.",
			'er_slug'           => "The '" . \WP_CLI_Helper::color( "[name]", "Y" ) . "' [type] slug is not valid.",
			'er_string'         => "'" . \WP_CLI_Helper::color( "[name]", "Y" ) . "' [type] version or source must be an string.",
		)
	),

	/**
	 * Global Configuration
	 */
	'config'        => array(

		# The list of acceptable options
		'options' => array(
			'default_clone_role',
			'db_host',
			'db_user',
			'db_password',
			'db_name',
			'admin_email',
			'admin_pass',
			'admin_user',
			'user_pass',
		),

		# Hidden Options
		'hidden'  => array( 'db_password', 'admin_pass' ),

		# List of Cli log for WP-CLI-APP Config
		'log'     => array(
			'process_time' => \WP_CLI_Helper::color( "(Process time: [time])", "P" )
		)
	),

	/**
	 * Commands log
	 */
	'command_log'   => WP_CLI_PACKAGIST_CACHE_PATH . '/log.json',

	/**
	 * WordPress Package System
	 */
	'package'       => array(

		# Default Package File name
		'file'                     => 'wordpress.json',

		# Save List of Wordpress Version in cache
		'version'                  => array(
			'file' => WP_CLI_PACKAGIST_CACHE_PATH . '/version.json',
			'age'  => 1, //Hour
		),

		# Save List Of Wordpress Locale in cache
		'locale'                   => array(
			'file' => WP_CLI_PACKAGIST_CACHE_PATH . '/locale.json',
			'age'  => 168, //168 Hour = One Week
		),

		# Package LocalTemp
		'localTemp'                => array(
			'path' => WP_CLI_PACKAGIST_HOME_PATH . '/tmp',
			'age'  => 8765, //1 year
			'type' => '.json'
		),

		# Save WordPress Download Url List
		'wordpress_core_url_file'  => WP_CLI_PACKAGIST_CACHE_PATH . '/core-url.json',

		# List of all WordPress Package Parameter
		'params'                   => array( /*'name', 'description', 'keywords',*/ 'core', 'config', 'dir', 'mysql', 'plugins', 'themes', 'commands' ),

		# List of Default Value in WordPress Package System
		'default'                  => array(
			'version'      => 'latest',
			'title'        => 'blog',
			'admin_user'   => 'admin',
			'admin_pass'   => 'admin',
			'admin_email'  => 'info@example.com',
			'locale'       => 'en_US',
			'network'      => false,
			'db_host'      => 'localhost',
			'db_user'      => 'root',
			'db_name'      => 'wordpress',
			'db_password'  => '',
			'table_prefix' => 'wp_',
			'db_charset'   => 'utf8mb4'
		),

		# Latest version keyword
		'latest'                   => array( 'master', 'last', 'latest', '*' ),

		# Preg Username and Package name
		'preg_username'            => '/[^a-zA-Z0-9-]/',

		# Preg cookie or rest-api Prefix
		'preg_prefix'              => '/[^a-zA-Z0-9-_]/',

		# Max number character description
		'max_desc_ch'              => 1000,

		# Max number Keywords
		'max_num_keywords'         => 5,

		# Max Number character Of every Keywords
		'max_keywords_ch'          => 30,

		# Separator between username and package
		'separator_name'           => '/',

		# Wp-cli default type for commands
		'wp-cli-command'           => array( 'wp-cli', 'wpcli' ),

		# Params class namespace
		'params_namespace'         => 'WP_CLI_PACKAGIST\Package\params\\',

		# Localhost domain
		'localhost_domain'         => array( 'localhost', '127.0.0.1' ),

		# Default Clone Role
		'default_clone_role'       => 'subscriber',

		# Check MySQL Character and Collation
		# @see https://dev.mysql.com/doc/refman/5.5/en/charset-charsets.html
		'mysql_character'          => array( 'big5', 'dec8', 'cp850', 'hp8', 'koi8r', 'latin1', 'latin2', 'swe7', 'ascii', 'ujis', 'sjis', 'hebrew', 'tis620', 'euckr', 'koi8u', 'gb2312', 'greek', 'cp1250', 'gbk', 'latin5', 'armscii8', 'utf8', 'ucs2', 'cp866', 'keybcs2', 'macce', 'macroman', 'cp852', 'latin7', 'utf8mb4', 'cp1251', 'utf16', 'cp1256', 'cp1257', 'utf32', 'geostd8', 'cp932', 'eucjpms' ),

		# List Of WordPress timeZone
		# We Use wp-includes\functions.php:5197 (wp_timezone_choice)
		'wordpress_timezone'       => '["Africa\/Abidjan","Africa\/Accra","Africa\/Addis_Ababa","Africa\/Algiers","Africa\/Asmara","Africa\/Bamako","Africa\/Bangui","Africa\/Banjul","Africa\/Bissau","Africa\/Blantyre","Africa\/Brazzaville","Africa\/Bujumbura","Africa\/Cairo","Africa\/Casablanca","Africa\/Ceuta","Africa\/Conakry","Africa\/Dakar","Africa\/Dar_es_Salaam","Africa\/Djibouti","Africa\/Douala","Africa\/El_Aaiun","Africa\/Freetown","Africa\/Gaborone","Africa\/Harare","Africa\/Johannesburg","Africa\/Juba","Africa\/Kampala","Africa\/Khartoum","Africa\/Kigali","Africa\/Kinshasa","Africa\/Lagos","Africa\/Libreville","Africa\/Lome","Africa\/Luanda","Africa\/Lubumbashi","Africa\/Lusaka","Africa\/Malabo","Africa\/Maputo","Africa\/Maseru","Africa\/Mbabane","Africa\/Mogadishu","Africa\/Monrovia","Africa\/Nairobi","Africa\/Ndjamena","Africa\/Niamey","Africa\/Nouakchott","Africa\/Ouagadougou","Africa\/Porto-Novo","Africa\/Sao_Tome","Africa\/Tripoli","Africa\/Tunis","Africa\/Windhoek","America\/Adak","America\/Anchorage","America\/Anguilla","America\/Antigua","America\/Araguaina","America\/Argentina\/Buenos_Aires","America\/Argentina\/Catamarca","America\/Argentina\/Cordoba","America\/Argentina\/Jujuy","America\/Argentina\/La_Rioja","America\/Argentina\/Mendoza","America\/Argentina\/Rio_Gallegos","America\/Argentina\/Salta","America\/Argentina\/San_Juan","America\/Argentina\/San_Luis","America\/Argentina\/Tucuman","America\/Argentina\/Ushuaia","America\/Aruba","America\/Asuncion","America\/Atikokan","America\/Bahia","America\/Bahia_Banderas","America\/Barbados","America\/Belem","America\/Belize","America\/Blanc-Sablon","America\/Boa_Vista","America\/Bogota","America\/Boise","America\/Cambridge_Bay","America\/Campo_Grande","America\/Cancun","America\/Caracas","America\/Cayenne","America\/Cayman","America\/Chicago","America\/Chihuahua","America\/Costa_Rica","America\/Creston","America\/Cuiaba","America\/Curacao","America\/Danmarkshavn","America\/Dawson","America\/Dawson_Creek","America\/Denver","America\/Detroit","America\/Dominica","America\/Edmonton","America\/Eirunepe","America\/El_Salvador","America\/Fortaleza","America\/Fort_Nelson","America\/Glace_Bay","America\/Godthab","America\/Goose_Bay","America\/Grand_Turk","America\/Grenada","America\/Guadeloupe","America\/Guatemala","America\/Guayaquil","America\/Guyana","America\/Halifax","America\/Havana","America\/Hermosillo","America\/Indiana\/Indianapolis","America\/Indiana\/Knox","America\/Indiana\/Marengo","America\/Indiana\/Petersburg","America\/Indiana\/Tell_City","America\/Indiana\/Vevay","America\/Indiana\/Vincennes","America\/Indiana\/Winamac","America\/Inuvik","America\/Iqaluit","America\/Jamaica","America\/Juneau","America\/Kentucky\/Louisville","America\/Kentucky\/Monticello","America\/Kralendijk","America\/La_Paz","America\/Lima","America\/Los_Angeles","America\/Lower_Princes","America\/Maceio","America\/Managua","America\/Manaus","America\/Marigot","America\/Martinique","America\/Matamoros","America\/Mazatlan","America\/Menominee","America\/Merida","America\/Metlakatla","America\/Mexico_City","America\/Miquelon","America\/Moncton","America\/Monterrey","America\/Montevideo","America\/Montserrat","America\/Nassau","America\/New_York","America\/Nipigon","America\/Nome","America\/Noronha","America\/North_Dakota\/Beulah","America\/North_Dakota\/Center","America\/North_Dakota\/New_Salem","America\/Ojinaga","America\/Panama","America\/Pangnirtung","America\/Paramaribo","America\/Phoenix","America\/Port-au-Prince","America\/Port_of_Spain","America\/Porto_Velho","America\/Puerto_Rico","America\/Rainy_River","America\/Rankin_Inlet","America\/Recife","America\/Regina","America\/Resolute","America\/Rio_Branco","America\/Santarem","America\/Santiago","America\/Santo_Domingo","America\/Sao_Paulo","America\/Scoresbysund","America\/Sitka","America\/St_Barthelemy","America\/St_Johns","America\/St_Kitts","America\/St_Lucia","America\/St_Thomas","America\/St_Vincent","America\/Swift_Current","America\/Tegucigalpa","America\/Thule","America\/Thunder_Bay","America\/Tijuana","America\/Toronto","America\/Tortola","America\/Vancouver","America\/Whitehorse","America\/Winnipeg","America\/Yakutat","America\/Yellowknife","Antarctica\/Casey","Antarctica\/Davis","Antarctica\/DumontDUrville","Antarctica\/Macquarie","Antarctica\/Mawson","Antarctica\/McMurdo","Antarctica\/Palmer","Antarctica\/Rothera","Antarctica\/Syowa","Antarctica\/Troll","Antarctica\/Vostok","Arctic\/Longyearbyen","Asia\/Aden","Asia\/Almaty","Asia\/Amman","Asia\/Anadyr","Asia\/Aqtau","Asia\/Aqtobe","Asia\/Ashgabat","Asia\/Atyrau","Asia\/Baghdad","Asia\/Bahrain","Asia\/Baku","Asia\/Bangkok","Asia\/Barnaul","Asia\/Beirut","Asia\/Bishkek","Asia\/Brunei","Asia\/Chita","Asia\/Choibalsan","Asia\/Colombo","Asia\/Damascus","Asia\/Dhaka","Asia\/Dili","Asia\/Dubai","Asia\/Dushanbe","Asia\/Famagusta","Asia\/Gaza","Asia\/Hebron","Asia\/Ho_Chi_Minh","Asia\/Hong_Kong","Asia\/Hovd","Asia\/Irkutsk","Asia\/Jakarta","Asia\/Jayapura","Asia\/Jerusalem","Asia\/Kabul","Asia\/Kamchatka","Asia\/Karachi","Asia\/Kathmandu","Asia\/Khandyga","Asia\/Kolkata","Asia\/Krasnoyarsk","Asia\/Kuala_Lumpur","Asia\/Kuching","Asia\/Kuwait","Asia\/Macau","Asia\/Magadan","Asia\/Makassar","Asia\/Manila","Asia\/Muscat","Asia\/Nicosia","Asia\/Novokuznetsk","Asia\/Novosibirsk","Asia\/Omsk","Asia\/Oral","Asia\/Phnom_Penh","Asia\/Pontianak","Asia\/Pyongyang","Asia\/Qatar","Asia\/Qyzylorda","Asia\/Riyadh","Asia\/Sakhalin","Asia\/Samarkand","Asia\/Seoul","Asia\/Shanghai","Asia\/Singapore","Asia\/Srednekolymsk","Asia\/Taipei","Asia\/Tashkent","Asia\/Tbilisi","Asia\/Tehran","Asia\/Thimphu","Asia\/Tokyo","Asia\/Tomsk","Asia\/Ulaanbaatar","Asia\/Urumqi","Asia\/Ust-Nera","Asia\/Vientiane","Asia\/Vladivostok","Asia\/Yakutsk","Asia\/Yangon","Asia\/Yekaterinburg","Asia\/Yerevan","Atlantic\/Azores","Atlantic\/Bermuda","Atlantic\/Canary","Atlantic\/Cape_Verde","Atlantic\/Faroe","Atlantic\/Madeira","Atlantic\/Reykjavik","Atlantic\/South_Georgia","Atlantic\/Stanley","Atlantic\/St_Helena","Australia\/Adelaide","Australia\/Brisbane","Australia\/Broken_Hill","Australia\/Currie","Australia\/Darwin","Australia\/Eucla","Australia\/Hobart","Australia\/Lindeman","Australia\/Lord_Howe","Australia\/Melbourne","Australia\/Perth","Australia\/Sydney","Europe\/Amsterdam","Europe\/Andorra","Europe\/Astrakhan","Europe\/Athens","Europe\/Belgrade","Europe\/Berlin","Europe\/Bratislava","Europe\/Brussels","Europe\/Bucharest","Europe\/Budapest","Europe\/Busingen","Europe\/Chisinau","Europe\/Copenhagen","Europe\/Dublin","Europe\/Gibraltar","Europe\/Guernsey","Europe\/Helsinki","Europe\/Isle_of_Man","Europe\/Istanbul","Europe\/Jersey","Europe\/Kaliningrad","Europe\/Kiev","Europe\/Kirov","Europe\/Lisbon","Europe\/Ljubljana","Europe\/London","Europe\/Luxembourg","Europe\/Madrid","Europe\/Malta","Europe\/Mariehamn","Europe\/Minsk","Europe\/Monaco","Europe\/Moscow","Europe\/Oslo","Europe\/Paris","Europe\/Podgorica","Europe\/Prague","Europe\/Riga","Europe\/Rome","Europe\/Samara","Europe\/San_Marino","Europe\/Sarajevo","Europe\/Saratov","Europe\/Simferopol","Europe\/Skopje","Europe\/Sofia","Europe\/Stockholm","Europe\/Tallinn","Europe\/Tirane","Europe\/Ulyanovsk","Europe\/Uzhgorod","Europe\/Vaduz","Europe\/Vatican","Europe\/Vienna","Europe\/Vilnius","Europe\/Volgograd","Europe\/Warsaw","Europe\/Zagreb","Europe\/Zaporozhye","Europe\/Zurich","Indian\/Antananarivo","Indian\/Chagos","Indian\/Christmas","Indian\/Cocos","Indian\/Comoro","Indian\/Kerguelen","Indian\/Mahe","Indian\/Maldives","Indian\/Mauritius","Indian\/Mayotte","Indian\/Reunion","Pacific\/Apia","Pacific\/Auckland","Pacific\/Bougainville","Pacific\/Chatham","Pacific\/Chuuk","Pacific\/Easter","Pacific\/Efate","Pacific\/Enderbury","Pacific\/Fakaofo","Pacific\/Fiji","Pacific\/Funafuti","Pacific\/Galapagos","Pacific\/Gambier","Pacific\/Guadalcanal","Pacific\/Guam","Pacific\/Honolulu","Pacific\/Johnston","Pacific\/Kiritimati","Pacific\/Kosrae","Pacific\/Kwajalein","Pacific\/Majuro","Pacific\/Marquesas","Pacific\/Midway","Pacific\/Nauru","Pacific\/Niue","Pacific\/Norfolk","Pacific\/Noumea","Pacific\/Pago_Pago","Pacific\/Palau","Pacific\/Pitcairn","Pacific\/Pohnpei","Pacific\/Port_Moresby","Pacific\/Rarotonga","Pacific\/Saipan","Pacific\/Tahiti","Pacific\/Tarawa","Pacific\/Tongatapu","Pacific\/Wake","Pacific\/Wallis","UTC-12","UTC-11.5","UTC-11","UTC-10.5","UTC-10","UTC-9.5","UTC-9","UTC-8.5","UTC-8","UTC-7.5","UTC-7","UTC-6.5","UTC-6","UTC-5.5","UTC-5","UTC-4.5","UTC-4","UTC-3.5","UTC-3","UTC-2.5","UTC-2","UTC-1.5","UTC-1","UTC-0.5","UTC+0","UTC+0.5","UTC+1","UTC+1.5","UTC+2","UTC+2.5","UTC+3","UTC+3.5","UTC+4","UTC+4.5","UTC+5","UTC+5.5","UTC+5.75","UTC+6","UTC+6.5","UTC+7","UTC+7.5","UTC+8","UTC+8.5","UTC+8.75","UTC+9","UTC+9.5","UTC+10","UTC+10.5","UTC+11","UTC+11.5","UTC+12","UTC+12.75","UTC+13","UTC+13.75","UTC+14"]',

		# Default REST API prefix
		'default_rest_prefix'      => 'wp-json',

		# List of commands that forbidden run in WordPress Package commands parameter
		'forbidden_wp_cli_command' => array( 'admin', 'cli', 'core', 'find', 'help', 'package', 'server', 'shell' ),

		# WordPress Table-prefix key in option or meta
		'tbl_prefix_key'           => '[table-prefix]',

		# Wordpress Default User Meta
		'default_user_meta'        => array(
			'nickname',
			'first_name',
			'last_name',
			'description',
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'locale',
			'[table-prefix]capabilities',
			'[table-prefix]user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
		),

		# WordPress Default Option
		# We Use wp-admin/includes/schema.php:413 (@since : v4.9.8)
		'default_wp_options'       => array( 'siteurl', 'home', 'blogname', 'blogdescription', 'users_can_register', 'admin_email', 'start_of_week', 'use_balanceTags', 'use_smilies', 'require_name_email', 'comments_notify', 'posts_per_rss', 'rss_use_excerpt', 'mailserver_url', 'mailserver_login', 'mailserver_pass', 'mailserver_port', 'default_category', 'default_comment_status', 'default_ping_status', 'default_pingback_flag', 'posts_per_page', 'date_format', 'time_format', 'links_updated_date_format', 'comment_moderation', 'moderation_notify', 'permalink_structure', 'rewrite_rules', 'hack_file', 'blog_charset', 'moderation_keys', 'active_plugins', 'category_base', 'ping_sites', 'comment_max_links', 'gmt_offset', 'default_email_category', 'recently_edited', 'template', 'stylesheet', 'comment_whitelist', 'blacklist_keys', 'comment_registration', 'html_type', 'use_trackback', 'default_role', 'db_version', 'uploads_use_yearmonth_folders', 'upload_path', 'blog_public', 'default_link_category', 'show_on_front', 'tag_base', 'show_avatars', 'avatar_rating', 'upload_url_path', 'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop', 'medium_size_w', 'medium_size_h', 'avatar_default', 'large_size_w', 'large_size_h', 'image_default_link_type', 'image_default_size', 'image_default_align', 'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order', 'sticky_posts', 'widget_categories', 'widget_text', 'widget_rss', 'uninstall_plugins', 'timezone_string', 'page_for_posts', 'page_on_front', 'default_post_format', 'link_manager_enabled', 'finished_splitting_shared_terms', 'site_icon', 'medium_large_size_w', 'medium_large_size_h', 'wp_page_for_privacy_policy', 'show_comments_cookies_opt_in' ),

		# WordPress Package anchor wp-config.php
		'wordpress_package_anchor' => '/* WordPress Package Constant */',
		'wp_package_end_anchor'    => '/* End of WordPress Package Constant */',

		# List of log for WordPress Package
		'log'                      => array(
			'created'               => 'Created WordPress package file.',
			'exist_pkg'             => "WordPress package file now exists. please delete it with '" . \WP_CLI_Helper::color( "wp pack remove", "Y" ) . "' , and try again.",
			'not_exist_pkg'         => 'WordPress package file is not exists.',
			'create_new_pkg'        => "Create new with '" . \WP_CLI_Helper::color( "wp init", "Y" ) . "'.",
			'er_pkg_syntax'         => 'The WordPress Package file syntax is wrong.',
			'exist_wp'              => 'WordPress files seem to already be present here.',
			'no_exist_pkg'          => 'There is no WordPress Package file.',
			'version_standard'      => "WordPress version is not standard. show available versions '" . \WP_CLI_Helper::color( "wp pack help", "Y" ) . "'.",
			'version_exist'         => "There is no WordPress with this version. show available versions '" . \WP_CLI_Helper::color( "wp pack help", "Y" ) . "'.",
			'wrong_locale'          => "WordPress Locale code is wrong. show complete list '" . \WP_CLI_Helper::color( "wp pack help", "Y" ) . "'.",
			'wrong_timezone'        => "WordPress Timezone is wrong. show complete list '" . \WP_CLI_Helper::color( "wp pack help", "Y" ) . "'.",
			'remove_pkg'            => 'Removed Wordpress package file.',
			'rm_pkg_confirm'        => 'Are you sure you want to remove WordPress package file ?',
			'not_exist_key'         => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' require " . \WP_CLI_Helper::color( "[require]", "B" ) . " parameter.",
			'empty_val'             => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' key is empty.",
			'is_string'             => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' must be an array.",
			'is_not_string'         => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' must be an string.",
			'is_boolean'            => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' must be an boolean.",
			'nv_url'                => "url in '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' is not valid.",
			'nv_duplicate'          => "The duplicate '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' value is in the " . \WP_CLI_Helper::color( "[array]", "B" ) . ".",
			'nv_duplicate_key'      => "The duplicate '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' Key is in the " . \WP_CLI_Helper::color( "[array]", "B" ) . ".",
			'nv_user_login'         => "The [which] in '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' may not be longer than 60 characters.",
			'nv_user_email'         => "The [which] format in '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' is not valid.",
			'er_db_connect'         => "Error connecting to MySQL database, Please check your inputs and the server.",
			'er_not_exist_db'       => "There is no MySQL database with the name of '" . \WP_CLI_Helper::color( "[name]", "Y" ) . "' in the database.",
			'er_exist_db_tbl'       => "There are " . \WP_CLI_Helper::color( "[table]", "Y" ) . " table[sum] in the " . \WP_CLI_Helper::color( "[name]", "B" ) . " database. Please remove it before install or use 'wp install --force'.",
			'pkg_is_valid'          => "WordPress package is valid.",
			'er_unknown_param'      => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' has an unknown parameter in the WordPress package.",
			'er_package_name'       => "Your package name is not valid.",
			'er_contain_html'       => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' contains the HTML code.",
			'er_special_ch'         => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' contains the special character.",
			'er_max_num_ch'         => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' should be at most [number] characters.",
			'er_valid'              => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' is not valid.",
			'er_max_item'           => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' should be at most [number] items.",
			'er_contain_space'      => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' contains white space. Use the dash instead of white space.",
			'er_forbidden'          => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' is forbidden.",
			'forbidden_role'        => "You can not change the role of the admin user. please remove '" . \WP_CLI_Helper::color( "role", "Y" ) . "' key from admin user.",
			'er_empty_source'       => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' [type] version or source is empty.",
			'er_wrong_version'      => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' [type] version or source is wrong.",
			'er_wrong_plugin_url'   => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' [type] source url is wrong.",
			'er_wrong_plugin_v'     => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' [type] version is wrong.",
			'er_plugin_activate'    => "The '" . \WP_CLI_Helper::color( "[slug]", "Y" ) . "' plugin activate parameter must be boolean.",
			'require_param_plugin'  => "The '" . \WP_CLI_Helper::color( "[slug]", "Y" ) . "' plugin require 'version' or 'url' parameter.",
			'path_contain_drive'    => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' should not contain the drive name.",
			'er_contain_drive_cmd'  => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' command path should not contain the drive name.",
			'er_string_command'     => "The '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' command type or path must be an string.",
			'er_register_cmd'       => "'" . \WP_CLI_Helper::color( "[key]", "Y" ) . "' is not recognized as an [where] command.",
			'er_forbidden_cmd'      => "The 'wp " . \WP_CLI_Helper::color( "[key]", "Y" ) . "' command is forbidden for running in WordPress Package.",
			'er_nightly_ver'        => "Nightly builds are only available for the en_US locale.",
			'er_found_release'      => "Release not found for this locale and version.",
			'er_incorrect_site_url' => "Your site URL is incorrect. Connection to '" . \WP_CLI_Helper::color( "[url]", "Y" ) . "' domain was not established.",
			'success_install'       => 'Completed install WordPress.',
			'success_update'        => 'Updated WordPress.',
			'network_domain_local'  => "Multi-site with subdomains cannot be configured when domain is '" . \WP_CLI_Helper::color( "[url]", "Y" ) . "'.",
			'get_wp'                => "[run] WordPress Core " . \WP_CLI_Helper::color( "([version])", "B" ) . ".",
			'create_config'         => "Create wp-config.php file.",
			'salt_generate'         => "Generate WordPress salt keys.",
			'added_db_const'        => "Added MySQL constants.",
			'change_cookie_prefix'  => "Change WordPress Cookie prefix.",
			'wp_sec_file'           => "WordPress security.",
			'removed_file'          => "Removed " . \WP_CLI_Helper::color( "[file]", "R" ) . " file.",
			'created_file'          => "Created " . \WP_CLI_Helper::color( "[file]", "B" ) . " file.",
			'sec_mu_plugins'        => "Disable direct access to " . \WP_CLI_Helper::color( "[file]", "B" ) . " file.",
			'change_dir'            => "Change WordPress folders.",
			'change_custom_folder'  => "Change " . \WP_CLI_Helper::color( "[folder]", "B" ) . " folder.",
			'create_db'             => "Created '[db_name]' Database.",
			'install_wp'            => "Install WordPress.",
			'install_wp_network'    => "Install WordPress Multisite Network.",
			'change_admin'          => "Changed admin [key].",
			'install_language'      => "install language [key].",
			'install_lang'          => "[key] Language installed.",
			'active_lang'           => "[key] Language activated.",
			'change_permalink'      => "Change the permalink structure.",
			'add_sites_network'     => "Creates blog in multi-site.",
			'created_site'          => "Created '[slug]' site.",
			'update_timezone'       => "Updated WordPress Timezone.",
			'change_timezone'       => "Change WordPress Timezone.",
			'update_options'        => "Update WordPress Options.",
			'er_autoload_opt'       => "The autoload value should be yes or no in '" . \WP_CLI_Helper::color( "[key]", "Y" ) . "'.",
			'create_users'          => "Create new WordPress users.",
			'create_one_user'       => "Created '[user_login]' user. " . \WP_CLI_Helper::color( "ID: [user_id]", "B" ) . "",
			'update_rest_api'       => "Update WordPress REST API.",
			'manage_item'           => "[work] '" . \WP_CLI_Helper::color( "[slug]", "Y" ) . "' [type].[more]",
			'manage_item_blue'      => "[work] " . \WP_CLI_Helper::color( "[key]", "B" ) . " [type].",
			'manage_item_red'       => "[work] " . \WP_CLI_Helper::color( "[key]", "R" ) . " [type].",
			'manage_item_error'     => \WP_CLI_Helper::color( "Error:", "R" ) . " [msg] '[key]'.",
			'install_wp_plugins'    => "Install WordPress plugins.",
			'install_wp_themes'     => "Install WordPress themes.",
			'er_delete_no_theme'    => "" . \WP_CLI_Helper::color( "Error", "R" ) . ": Can't delete the currently active theme '[theme]'",
			'is_now_theme_active'   => "The '[stylesheet]' theme is already active.",
			'theme_not_found'       => "The '" . \WP_CLI_Helper::color( "[stylesheet]", "R" ) . "' theme was not found for switching in your WordPress.",
			'switch_to_theme'       => "Switched to '[stylesheet]' theme.",
			'run_pkg_commands'      => "Run WordPress package commands.",
			'run_cmd'               => "Run '" . \WP_CLI_Helper::color( "[cmd]", "B" ) . "' command[more].",
			'er_find_dir_cmd'       => "The '" . \WP_CLI_Helper::color( "[dir]", "R" ) . "' path is not found for running '[cmd]'.",
			'item_log'              => "[run] " . \WP_CLI_Helper::color( "[key]", "B" ) . " [what].",
			'rm_item_log'           => "[run] " . \WP_CLI_Helper::color( "[key]", "R" ) . " [what].",
			'dup_admin_user'        => "The [what] is duplicate between the admin and a user.",
			'not_change_pkg'        => "No changes found in the WordPress package.",
			'srdb_uploads'          => "Updated WordPress attachments link in the database.",
			'convert_single_multi'  => "Convert WordPress single-site to multi-site.",
			'change_subdomain_type' => "[work] WordPress multi-site subdomain.",
		)
	),

	/**
	 * Curl
	 */
	'curl'          => array(

		# Default User Agent for request
		'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',

		# List of cli log
		'log'        => array(
			'er_enabled' => "Please install/enable the cURL PHP extension.",
			'er_url'     => "The '" . \WP_CLI_Helper::color( "[what]", "Y" ) . "' url is wrong. please check url and try again.",
			'er_zip'     => "The '" . \WP_CLI_Helper::color( "[what]", "Y" ) . "' url must be a zip file.",
			'er_connect' => "Failed to connect to " . \WP_CLI_Helper::color( "[url]", "Y" ) . ". please check url or your internet connection."
		)
	)

);