<?php

class Xcloner_Settings
{
	private $logger_file = "xcloner_main_%s.log";
	private $logger_file_hash = "xcloner%s.log";
	private $hash ;
	
	public function __construct($hash = "")
	{
		if(isset($hash))
			$this->set_hash($hash);
	}
	
	public function get_logger_filename($include_hash = 0)
	{
		if($include_hash)
			$filename = sprintf($this->logger_file_hash, $this->get_hash()); 
		else
			$filename = sprintf($this->logger_file, $this->get_server_unique_hash(5));
		
		return $filename;
	}
	
	public function get_xcloner_start_path()
	{
		if(!get_option('xcloner_start_path') or !is_dir(get_option('xcloner_start_path')))
			$path = realpath(ABSPATH);
		else
			$path = get_option('xcloner_start_path');
		
		return $path;
	}
	
	public function get_xcloner_dir_path($dir)
	{
		$path = self::get_xcloner_start_path().DS.$dir;
		
		return $path;
	}
	
	public function get_xcloner_store_path()
	{
		if(!get_option('xcloner_store_path')  or !is_dir(get_option('xcloner_store_path')))
			$path = realpath(XCLONER_STORAGE_PATH);
		else
			$path = get_option('xcloner_store_path');
		
		return $path;
	}
	
	public function get_xcloner_tmp_path_suffix()
	{
		return "xcloner-".$this->get_server_unique_hash(5);
	}
	
	
	public function get_xcloner_tmp_path()
	{
		$path = sys_get_temp_dir().DS.".".$this->get_xcloner_tmp_path_suffix();
		if(!is_dir($path))
		{
			mkdir($path);
			chmod($path, 0777);
		}
		
		return $path;
	}
	
	public function get_enable_mysql_backup()
	{
		if(get_option('xcloner_enable_mysql_backup'))
			return true;
		
		return false;	
	}
	
	public function get_backup_extension_name($ext = "")
	{
		if(!$ext)
		{
			if(get_option('xcloner_backup_compression_level'))
				$ext = ".tgz";
			else
				$ext = ".tar";
		}	
		return ($this->get_hash()).$ext;	
	}
	
	public function get_hash()
	{
		if(!$this->hash){
			$this->set_hash();
		}
		
		//echo $this->hash;	
		return $this->hash;
	}
	
	public function generate_new_hash()
	{
		$hash = "-".md5(rand());
		
		$this->set_hash(substr( $hash, 0, 6));
		
		return $hash;
	}
	
	public function set_hash($hash = "")
	{
		$this->hash = substr( $hash, 0, 6);
		
		return $this;
	}
	
	public function get_default_backup_name()
	{
		$data = parse_url(get_site_url());
			
		$backup_name = "backup_[domain]".(isset($data['port'])?":".$data['port']:"")."-[time]-".($this->get_enable_mysql_backup()?"sql":"nosql");
		
		return $backup_name;
	}
	
	public function get_db_hostname()
	{
		global $wpdb;
		
		if(!$data = get_option('xcloner_mysql_hostname'))
			$data = $wpdb->dbhost;
		
		return $data;
	}
	
	public function get_db_username()
	{
		global $wpdb;
		
		if(!$data = get_option('xcloner_mysql_username'))
			$data = $wpdb->dbuser;
		
		return $data;
	}
	
	public function get_db_password()
	{
		global $wpdb;
		
		if(!$data = get_option('xcloner_mysql_password'))
			$data = $wpdb->dbpassword;
		
		return $data;
	}
	
	public function get_db_database()
	{
		global $wpdb;
		
		if(!$data = get_option('xcloner_mysql_database'))
			$data = $wpdb->dbname;
		
		return $data;
	}
	
	public function get_table_prefix()
	{
		global $wpdb;
		
		return $wpdb->prefix;
	}
	
	public function get_xcloner_option($option)
	{
		$data = get_option($option);
		
		return $data;
	}
	
	public function get_server_unique_hash($strlen = 0)
	{
		$hash = md5(get_home_url().__DIR__);
		
		if($strlen)
			$hash = substr($hash, 0, $strlen);
			
		return $hash;
	}
	
	public function settings_init()
	{
	    global $wpdb;
	    $this->xcloner_sanitization = new Xcloner_Sanitization();
	    
	    //ADDING MISSING OPTIONS
	    if( false == get_option( 'xcloner_mysql_settings_page' ) ) {  
			add_option( 'xcloner_mysql_settings_page' );
		} // end if
		
	    if( false == get_option( 'xcloner_cron_settings_page' ) ) {  
			add_option( 'xcloner_cron_settings_page' );
		} // end if
	    
	    if( false == get_option( 'xcloner_system_settings_page' ) ) {  
			add_option( 'xcloner_system_settings_page' );
		} // end if
		
	    if( false == get_option( 'xcloner_cleanup_settings_page' ) ) {  
			add_option( 'xcloner_cleanup_settings_page' );
		} // end if
	 
	    
	    //ADDING SETTING SECTIONS
	    //GENERAL section
	    add_settings_section(
	        'xcloner_general_settings_group',
	        __(' '),
	        array($this, 'xcloner_settings_section_cb'),
	        'xcloner_settings_page'
	    );
	    //MYSQL section
	    add_settings_section(
	        'xcloner_mysql_settings_group',
	        __(' '),
	        array($this, 'xcloner_settings_section_cb'),
	        'xcloner_mysql_settings_page'
	    );
	    
	    //SYSTEM section
	    add_settings_section(
	        'xcloner_system_settings_group',
	        __('These are advanced options recommended for developers!','xcloner'),
	        array($this, 'xcloner_settings_section_cb'),
	        'xcloner_system_settings_page'
	    );
	    
	    //CLEANUP section
	    add_settings_section(
	        'xcloner_cleanup_settings_group',
	        __(' '),
	        array($this, 'xcloner_settings_section_cb'),
	        'xcloner_cleanup_settings_page'
	    );
	    
		
		//CRON section
	    add_settings_section(
	        'xcloner_cron_settings_group',
	        __(' '),
	        array($this, 'xcloner_settings_section_cb'),
	        'xcloner_cron_settings_page'
	    );
	    
	    
	    
		//REGISTERING THE 'GENERAL SECTION' FIELDS
		register_setting('xcloner_general_settings_group', 'xcloner_backup_compression_level', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_backup_compression_level',
	       __('Backup Compression Level','xcloner'),
	        array($this, 'do_form_range_field'),
	        'xcloner_settings_page',
	        'xcloner_general_settings_group',
	        array('xcloner_backup_compression_level',
	         __('Options between [0-9]. Value 0 means no compression, while 9 is maximum compression affecting cpu load','xcloner'), 
	         0,
	         9
	         )
	    );
	    
	    register_setting('xcloner_general_settings_group', 'xcloner_start_path', array($this->xcloner_sanitization, "sanitize_input_as_absolute_path"));
	    add_settings_field(
	        'xcloner_start_path',
	        __('Backup Start Location','xcloner'),
	        array($this, 'do_form_text_field'),
	        'xcloner_settings_page',
	        'xcloner_general_settings_group',
	        array('xcloner_start_path',
				__('Base path location from where XCloner can start the Backup.','xcloner'),
				$this->get_xcloner_start_path(),
				//'disabled'
				)
	    );
	    
	    register_setting('xcloner_general_settings_group', 'xcloner_store_path', array($this->xcloner_sanitization, "sanitize_input_as_absolute_path"));
	    add_settings_field(
	        'xcloner_store_path',
	        __('Backup Storage Location','xcloner'),
	        array($this, 'do_form_text_field'),
	        'xcloner_settings_page',
	        'xcloner_general_settings_group',
	        array('xcloner_store_path',
				__('Location where XCloner will store the Backup archives.','xcloner'),
				$this->get_xcloner_store_path(), 
				//'disabled'
				)
	    );
	    
	    register_setting('xcloner_general_settings_group', 'xcloner_enable_log', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_enable_log',
	        __('Enable XCloner Backup Log','xcloner'),
	        array($this, 'do_form_switch_field'),
	        'xcloner_settings_page',
	        'xcloner_general_settings_group',
	        array('xcloner_enable_log',
				sprintf(__('Enable the XCloner Backup log. You will find it stored unde the Backup Storage Location, file %s','xcloner'), $this->get_logger_filename())
				)
		);	
	 
		//REGISTERING THE 'MYSQL SECTION' FIELDS
		register_setting('xcloner_mysql_settings_group', 'xcloner_enable_mysql_backup', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_enable_mysql_backup',
	        __('Enable Mysql Backup','xcloner'),
	        array($this, 'do_form_switch_field'),
	        'xcloner_mysql_settings_page',
	        'xcloner_mysql_settings_group',
	        array('xcloner_enable_mysql_backup',
				__('Enable Mysql Backup Option. If you don\'t want to backup the database, you can disable this.','xcloner')
				)
	    );
	    
	    register_setting('xcloner_mysql_settings_group', 'xcloner_backup_only_wp_tables');
	    add_settings_field(
	        'xcloner_backup_only_wp_tables',
	        __('Backup only WP tables','xcloner'),
	        array($this, 'do_form_switch_field'),
	        'xcloner_mysql_settings_page',
	        'xcloner_mysql_settings_group',
	        array('xcloner_backup_only_wp_tables',
				sprintf(__('Enable this if you only want to Backup only tables starting with \'%s\' prefix','xcloner'), $this->get_table_prefix())
				)
	    );
	    
	    register_setting('xcloner_mysql_settings_group', 'xcloner_mysql_hostname', array($this->xcloner_sanitization, "sanitize_input_as_raw"));
	    add_settings_field(
	        'xcloner_mysql_hostname',
	        __('Mysql Hostname','xcloner'),
	        array($this, 'do_form_text_field'),
	        'xcloner_mysql_settings_page',
	        'xcloner_mysql_settings_group',
	        array('xcloner_mysql_hostname',
				__('Wordpress mysql hostname','xcloner'),
				$this->get_db_hostname(),
				'disabled'
				)
	    );

	    register_setting('xcloner_mysql_settings_group', 'xcloner_mysql_username', array($this->xcloner_sanitization, "sanitize_input_as_raw"));
	    add_settings_field(
	        'xcloner_mysql_username',
	        __('Mysql Username','xcloner'),
	        array($this, 'do_form_text_field'),
	        'xcloner_mysql_settings_page',
	        'xcloner_mysql_settings_group',
	        array('xcloner_mysql_username',
				__('Wordpress mysql username','xcloner'),
				$this->get_db_username(),
				'disabled'
				)
	    );
	    
	    register_setting('xcloner_mysql_settings_group', 'xcloner_mysql_database', array($this->xcloner_sanitization, "sanitize_input_as_raw"));
	    add_settings_field(
	        'xcloner_mysql_database',
	        __('Mysql Database','xcloner'),
	        array($this, 'do_form_text_field'),
	        'xcloner_mysql_settings_page',
	        'xcloner_mysql_settings_group',
	        array('xcloner_mysql_database',
				__('Wordpress mysql database','xcloner'),
				$this->get_db_database(),
				'disabled'
				)
	    );
	    
	    //REGISTERING THE 'SYSTEM SECTION' FIELDS
	    register_setting('xcloner_system_settings_group', 'xcloner_size_limit_per_request', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_size_limit_per_request',
	       __('Data Size Limit Per Request','xcloner'),
	        array($this, 'do_form_range_field'),
	        'xcloner_system_settings_page',
	        'xcloner_system_settings_group',
	        array('xcloner_size_limit_per_request',
	         __('Use this option to set how much file data can XCloner backup in one AJAX request. Range 0-100 MB','xcloner'), 
	         0,
	         100
	         )
	    );
	    
		register_setting('xcloner_system_settings_group', 'xcloner_files_to_process_per_request', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_files_to_process_per_request',
	       __('Files To Process Per Request','xcloner'),
	        array($this, 'do_form_range_field'),
	        'xcloner_system_settings_page',
	        'xcloner_system_settings_group',
	        array('xcloner_files_to_process_per_request',
	         __('Use this option to set how many files XCloner should process at one time before doing another AJAX call','xcloner'), 
	         0,
	         1000
	         )
	    );
	    
		register_setting('xcloner_system_settings_group', 'xcloner_directories_to_scan_per_request', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_directories_to_scan_per_request',
	       __('Directories To Scan Per Request','xcloner'),
	        array($this, 'do_form_range_field'),
	        'xcloner_system_settings_page',
	        'xcloner_system_settings_group',
	        array('xcloner_directories_to_scan_per_request',
	         __('Use this option to set how many directories XCloner should scan at one time before doing another AJAX call','xcloner'), 
	         0,
	         1000
	         )
	    );
	    
		register_setting('xcloner_system_settings_group', 'xcloner_database_records_per_request', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_database_records_per_request',
	       __('Database Records Per Request','xcloner'),
	        array($this, 'do_form_range_field'),
	        'xcloner_system_settings_page',
	        'xcloner_system_settings_group',
	        array('xcloner_database_records_per_request',
	         __('Use this option to set how many database table records should be fetched per AJAX request, or set to 0 to fetch all.  Range 0-100000 records','xcloner'), 
	         0,
	         100000
	         )
	    );
	    
		register_setting('xcloner_system_settings_group', 'xcloner_exclude_files_larger_than_mb', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_exclude_files_larger_than_mb',
	       __('Exclude files larger than (MB)','xcloner'),
	        array($this, 'do_form_number_field'),
	        'xcloner_system_settings_page',
	        'xcloner_system_settings_group',
	        array('xcloner_exclude_files_larger_than_mb',
	         __('Use this option to automatically exclude files larger than a certain size in MB, or set to -1 to include all. Range 0-1000 MB','xcloner'), 
	         )
	    );
	    
		register_setting('xcloner_system_settings_group', 'xcloner_split_backup_limit', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_split_backup_limit',
	       __('Split Backup Archive Limit (MB)','xcloner'),
	        array($this, 'do_form_number_field'),
	        'xcloner_system_settings_page',
	        'xcloner_system_settings_group',
	        array('xcloner_split_backup_limit',
	         __('Use this option to automatically split the backup archive into smaller parts. Range  0-10000 MB','xcloner'), 
	         )
	    );
		
		//REGISTERING THE 'CLEANUP SECTION' FIELDS
		register_setting('xcloner_cleanup_settings_group', 'xcloner_cleanup_retention_limit_days', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_cleanup_retention_limit_days',
	        __('Cleanup by Date(days)','xcloner'),
	        array($this, 'do_form_number_field'),
	        'xcloner_cleanup_settings_page',
	        'xcloner_cleanup_settings_group',
	        array('xcloner_cleanup_retention_limit_days',
				__('Specify the maximum number of days a backup archive can be kept on the server. 0 disables this option','xcloner')
			)
	    );
	    
		register_setting('xcloner_cleanup_settings_group', 'xcloner_cleanup_retention_limit_archives', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_cleanup_retention_limit_archives',
	        __('Cleanup by Quantity','xcloner'),
	        array($this, 'do_form_number_field'),
	        'xcloner_cleanup_settings_page',
	        'xcloner_cleanup_settings_group',
	        array('xcloner_cleanup_retention_limit_archives',
				__('Specify the maximum number of backup archives to keep on the server. 0 disables this option','xcloner')
			)
	    );
	    
		register_setting('xcloner_cleanup_settings_group', 'xcloner_cleanup_capacity_limit', array($this->xcloner_sanitization, "sanitize_input_as_int"));
	    add_settings_field(
	        'xcloner_cleanup_capacity_limit',
	        __('Cleanup by Capacity(MB)','xcloner'),
	        array($this, 'do_form_number_field'),
	        'xcloner_cleanup_settings_page',
	        'xcloner_cleanup_settings_group',
	        array('xcloner_cleanup_capacity_limit',
				__('Remove oldest backups if all created backups exceed the configured limit in Megabytes. 0 disables this option','xcloner')
			)
	    );
		
		//REGISTERING THE 'CRON SECTION' FIELDS
		register_setting('xcloner_cron_settings_group', 'xcloner_cron_frequency');
	    add_settings_field(
	        'xcloner_cron_frequency',
	        __('Cron frequency','xcloner'),
	        array($this, 'do_form_text_field'),
	        'xcloner_cron_settings_page',
	        'xcloner_cron_settings_group',
	        array('xcloner_cron_frequency',
				__('Cron frequency')
			)
	    );
	}
	 
	
	
	 
	/**
	 * callback functions
	 */
	 
	// section content cb
	public function xcloner_settings_section_cb()
	{
	    //echo '<p>WPOrg Section Introduction.</p>';
	}
	 
	// text field content cb
	public function do_form_text_field($params)
	{
		if(!isset($params['3']))
			$params[3] = 0;
		if(!isset($params['2']))
			$params[2] = 0;	
			
		list($fieldname, $label, $value, $disabled) = $params;
		
		if(!$value)
			$value = get_option($fieldname);
	    // output the field
	    ?>
	    <div class="row">
	        <div class="input-field col s10 m10 l6">
	          <input class="validate" <?php echo ($disabled)?"disabled":""?> name="<?php echo $fieldname?>" id="<?php echo $fieldname?>" type="text" class="validate" value="<?php echo isset($value) ? esc_attr($value) : ''; ?>">
	        </div>
	        <div class="col s2 m2 ">
				<a class="btn-floating tooltipped btn-small" data-position="right" data-delay="50" data-tooltip="<?php echo $label?>" data-tooltip-id=""><i class="material-icons">help_outline</i></a>
	        </div>
	    </div>
		

	    <?php
	}
	
	// number field content cb
	public function do_form_number_field($params)
	{
		if(!isset($params['3']))
			$params[3] = 0;
		if(!isset($params['2']))
			$params[2] = 0;	
			
		list($fieldname, $label, $value, $disabled) = $params;
		
		if(!$value)
			$value = get_option($fieldname);
	    // output the field
	    ?>
	    <div class="row">
	        <div class="input-field col s5 m5 l3">
	          <input class="validate" <?php echo ($disabled)?"disabled":""?> name="<?php echo $fieldname?>" id="<?php echo $fieldname?>" type="number" class="validate" value="<?php echo isset($value) ? esc_attr($value) : ''; ?>">
	        </div>
	        <div class="col s2 m2 ">
				<a class="btn-floating tooltipped btn-small" data-position="right" data-delay="50" data-tooltip="<?php echo $label?>" data-tooltip-id=""><i class="material-icons">help_outline</i></a>
	        </div>
	    </div>
		

	    <?php
	}
	
	public function do_form_range_field($params)
	{
		if(!isset($params['4']))
			$params[4] = 0;
			
		list($fieldname, $label, $range_start, $range_end, $disabled) = $params;
		$value = get_option($fieldname);
	?>
		<div class="row">
	        <div class="input-field col s10 m10 l6">
				<p class="range-field">
			      <input <?php echo ($disabled)?"disabled":""?> type="range" name="<?php echo $fieldname?>" id="<?php echo $fieldname?>" min="<?php echo $range_start?>" max="<?php echo $range_end?>" value="<?php echo isset($value) ? esc_attr($value) : ''; ?>" />
			    </p>
			</div>
			<div class="col s2 m2 ">
				<a class="btn-floating tooltipped btn-small" data-position="right" data-delay="50" data-tooltip="<?php echo $label?>" data-tooltip-id=""><i class="material-icons">help_outline</i></a>
	        </div>    
		</div>	
	<?php
	}
	
	
	public function do_form_switch_field($params)
	{
		if(!isset($params['2']))
			$params[2] = 0;
		list($fieldname, $label, $disabled) = $params;
		$value = get_option($fieldname);
	?>
	<div class="row">
		<div class="input-field col s10 m10 l6">	
			<div class="switch">
				<label>
				  Off
				  <input <?php echo ($disabled)?"disabled":""?> type="checkbox" name="<?php echo $fieldname?>" id="<?php echo $fieldname?>" value="1" <?php echo ($value) ? 'checked="checked"' : ''; ?>">
				  <span class="lever"></span>
				  On
				</label>
			</div>
		</div> 
		<div class="col s2 m2">
				<a class="btn-floating tooltipped btn-small" data-position="right" data-delay="50" data-tooltip="<?php echo $label?>" data-tooltip-id=""><i class="material-icons">help_outline</i></a>
	        </div>   
	</div>
	<?php
	}
}
