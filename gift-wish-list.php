<?php
/**
 * Plugin Name: Gift Wish List
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: A brief description of the Plugin.
 * Version: 0.1
 * Author: Name Of The Plugin Author
 * Author URI: http://URI_Of_The_Plugin_Author
 * License: A "Slug" license name e.g. GPL2
 */
 

class Gift_Wish_List {
	const DB_VERSION_OPTION = 'giftwishlist_db_version';
	const DB_VERSION = '1.0';

	const TABLE_USERS = 'users';
	const TABLE_FAMILIES = 'families';
	const TABLE_FAMILY_MEMBERS = 'family_members';
	const TABLE_LIST_ITEMS = 'list_items';
	const TABLE_LIST_ITEMS_USER_JOIN = 'list_items_user_join';
	const TABLE_SETTINGS = 'settings';

	#======================================================================
	# Admin handlers
	#======================================================================
	
	const SETTINGS_PERMISSION_REQUIRED = 'manage_options';
	
	const UPLOAD_FORM_SUBMIT_FIELD = 'gwl_upload';
	const UPLOAD_FORM_LISTFILE_FIELD = 'gwl_listfile';
	const UPLOAD_FORM_REPLACE_FIELD = 'gwl_replace';
	const UPLOAD_FORM_NONCE_STRING = 'gift-wish-list-upload';
	
	const SETTINGS_FORM_SET_SUBMIT_FIELD = 'gwl_setting_set';
	const SETTINGS_FORM_NAME_FIELD = 'gwl_setting_name';
	const SETTINGS_FORM_VALUE_FIELD = 'gwl_setting_value';

	static function admin_menu() {
		add_options_page( __( 'Gift Wish List', 'gift-wish-list' ),
						  __( 'Gift Wish List Settings', 'gift-wish-list' ),
						  self::SETTINGS_PERMISSION_REQUIRED,
						  'giftwishlistsettings',
						  array( 'Gift_Wish_List', 'settings_page' ) );
	}
	
	static function settings_page() {
		global $wpdb;
		
		if ( !current_user_can( self::SETTINGS_PERMISSION_REQUIRED ) ) {
	    	wp_die( __( 'You do not have sufficient permissions to access this page.', 'gift-wish-list' ) );
	    }
		
		//print_r($_POST);
		
		// Update the wish list if posting
		if ( isset( $_POST[ self::UPLOAD_FORM_SUBMIT_FIELD ] ) ) {
			check_admin_referer( self::UPLOAD_FORM_NONCE_STRING );
			
			$replace_data = $_POST[ self::UPLOAD_FORM_REPLACE_FIELD ] == 'true';
			
			$err = self::process_wish_list_post( $wpdb, $replace_data );
			if ( $err ) {
				// Display an error message
?>
<div class="error"><p><strong><?php printf( __( 'Error uploading wish list: %s', 'gift-wish-list' ), $err ); ?></strong></p></div>
<?php
			}
			else {
?>
<div class="updated"><p><strong><?php _e( 'Wish list updated', 'gift-wish-list' ); ?></strong></p></div>
<?php
			}
		}

		// Update the settings list if posting
		if ( isset( $_POST[ self::SETTINGS_FORM_SET_SUBMIT_FIELD ] ) )
		{
			$set_name = strtoupper(trim($_POST[self::SETTINGS_FORM_NAME_FIELD]));
			$set_value = $_POST[self::SETTINGS_FORM_VALUE_FIELD];

			if ($set_name && $set_name != "")
			{
				if (!self::setting_set($set_name,$set_value,true))
				{
					$setting_error = "<div class=\"error\"><p><strong>" . __( 'Could not set value: ', 'gift-wish-list' ) . $set_name . "</strong></p></div>";
 				}
				else
				{
					$setting_error = "<div class=\"updated\"><p><strong>" . __( 'Updated value: ', 'gift-wish-list' ) . $set_name . "</strong></p></div>";
				}
			}
			else
			{
				$setting_error = "<div class=\"error\"><p><strong>" . __( 'Setting must have a non-empty name', 'gift-wish-list' ) . "</strong></p></div>";
			}
		}

		// Display the settings form
?>
<div class="wrap">
  <h2><?php echo __( 'Gift Wish List Data', 'gift-wish-list' ) ?></h2>
  <form enctype="multipart/form-data" action="" method="POST">
    <?php wp_nonce_field( self::UPLOAD_FORM_NONCE_STRING ); ?>
    <table class="form-table">
	  <tr>
	    <th scope="row"><label for="<?php echo self::UPLOAD_FORM_LISTFILE_FIELD; ?>">Wish List File</label></th>
      	<td><input name="<?php echo self::UPLOAD_FORM_LISTFILE_FIELD; ?>" type="file"/></td>
      </tr>
      <tr>
      	<th scope="row"><label for="<?php echo self::UPLOAD_FORM_REPLACE_FIELD; ?>">Replace Existing Data</label></th>
      	<td><input name="<?php echo self::UPLOAD_FORM_REPLACE_FIELD; ?>" type="radio" value="true" checked="true">Replace entire list</input> <br />
      		<input name="<?php echo self::UPLOAD_FORM_REPLACE_FIELD; ?>" type="radio" value="false">Add to existing list</input>
      	</td>
      </tr>
    </table>
    <p class="submit">
      <input class="button-primary" name="<?php echo self::UPLOAD_FORM_SUBMIT_FIELD; ?>" type="submit" value="<?php esc_attr_e('Upload List') ?>" />
    </p>
  </form>

  <h2><?php echo __( 'Gift Wish List Settings', 'gift-wish-list' ) ?></h2>
  <?php
    if (isset($setting_error))
    {
      echo $setting_error;
    }
  ?>
  <?php
  	$all_settings = self::setting_get_all();
    foreach ($all_settings as $name => $value)
    {
  ?>
    <form method="post">
      <span class="setting_name"><?php echo $name; ?></span>
      <input type="hidden" name="<?php echo self::SETTINGS_FORM_NAME_FIELD; ?>" maxlength=50 size=50 value="<?php echo $name; ?>"/>
      <input type="text" name="<?php echo self::SETTINGS_FORM_VALUE_FIELD; ?>" maxlength=250 size=50 value="<?php echo $value; ?>"/>
      <input class="button-primary" type="submit" name="<?php echo self::SETTINGS_FORM_SET_SUBMIT_FIELD; ?>" value="Update"/>
    </form>
  <?php
    }

  ?>
  <b>Add New Setting</b><br/>
  <form method="post">
	<label for="<?php echo self::SETTINGS_FORM_NAME_FIELD; ?>">Name</label>
	<input type="text" name="<?php echo self::SETTINGS_FORM_NAME_FIELD; ?>" maxlength=50 size=50/>
	<label for="<?php echo self::SETTINGS_FORM_VALUE_FIELD; ?>">Value</label>
	<input type="text" name="<?php echo self::SETTINGS_FORM_VALUE_FIELD; ?>" maxlength=250 size=50/>
    <input class="button-primary" type="submit" name="<?php echo self::SETTINGS_FORM_SET_SUBMIT_FIELD; ?>" value="Add"/>
  </form>
 
</div>

<?php
	}
	
	private static function process_wish_list_post( $wpdb, $replace_data ) {
        $upload_error = NULL;
		
		$wpdb->show_errors();
        
 		// Check to make sure a file was uploaded
		if ( $_FILES[self::UPLOAD_FORM_LISTFILE_FIELD] && $_FILES[self::UPLOAD_FORM_LISTFILE_FIELD]['tmp_name'] ) {
			$filename = $_FILES[self::UPLOAD_FORM_LISTFILE_FIELD]['tmp_name'];
			try {
				// Start transaction
				if ( false === $wpdb->query( 'BEGIN' ) ) {
					$wpdb->print_error();
					throw new Exception( __( 'Could not begin database transaction' ) );
				}

				// Delete existing records
				if ( $replace_data ) {
					if ( $wpdb->query( "DELETE FROM " . self::get_table_name( self::TABLE_FAMILIES  ) ) === FALSE ) {
						throw new Exception( __( 'Could not delete existing list items', 'gift-wish-list' ) );
					}
				}

				self::import_wish_list_csv( $filename, $wpdb );

				// commit transaction
				if ( false === $wpdb->query( 'COMMIT' ) ) {
					throw new Exception( __( 'Could not commit to database' ) );
				}
			} catch ( Exception $e ) {
				// rollback transaction
				$wpdb->query( 'ROLLBACK' );
				$upload_error = $e->getMessage();
			}
		} else {
			$upload_error = __( 'No file uploaded', 'gift-wish-list' );
		}

		$wpdb->hide_errors();
		
		return $upload_error;
	}

	private static function import_wish_list_csv( $filename, $wpdb )
	{
	    $families_table_name = self::get_table_name( self::TABLE_FAMILIES );
        $family_members_table_name = self::get_table_name( self::TABLE_FAMILY_MEMBERS );
        $items_table_name = self::get_table_name( self::TABLE_LIST_ITEMS );
        
		$in = fopen( $filename, "r" );
		if ( !$in ) {
			throw new Exception( __( 'Cannot open file', 'gift-wish-list' ) );
		}
		
		$current_family = NULL;
		$current_member = NULL;
		  
		while ( $line = fgetcsv( $in ) )
		{
			$fields = self::get_wish_list_fields( $line );
		
			// Skip empty and comment lines
			if ( count( $fields ) == 0 || substr( $fields[0], 0, 1 ) == "#" ) {
				continue;
			}
		
			// Act on the keyword
			switch ( strtolower( $fields[0] ) )
			{
		  	case "family":
		    	if ( count( $fields ) != 2 ) {
		    		 throw new Exception( __('Incorrect field count for "family" keyword', 'gift-wish-list' ) );
				}
		    	$name = $fields[1];
		
			    if ( ! $wpdb->insert( $families_table_name, array( 'name' => $name ) ) ) {
			        throw new Exception( 'Couldn\'t insert family: '.$insert_query );
                }
			    $current_family = $wpdb->insert_id;
			    $current_member = NULL;
			
			    break;
		
            case "person":
                if ( count( $fields ) != 4 ) {
                    throw new Exception( __( 'Incorrect field count for "person" keyword', 'gift-wish-list' ) );
                }
                $age = $fields[1];
                if ( !is_numeric( $age ) ) {
                    throw new Exception( __( 'Age must be numeric', 'gift-wish-list' ) );
                }
                $gender = strtolower( $fields[2] );
		        if ( $gender != 'm' && $gender != 'f' && $gender != 'g' ) {
                    throw new Exception( __( 'Gender must be \'m\', \'f\' or \'g\'', 'gift-wish-list' ) );
                }
		        $name = $fields[3];
		
		        if ( $current_family == NULL ) {
		            throw new Exception( sprintf( __( 'No family for member \'%s\'', 'gift-wish-list' ), htmlentities( $name ) ) );
                }

           	    if ( ! $wpdb->insert( $family_members_table_name,
                                      array(
                                        'family_id' => $current_family,
		                                'age' => $age,
		                                'gender' => $gender,
		                                'name' => $name
		                              ),
		                              array(
		                                '%d',
		                                '%d',
		                                '%s',
		                                '%s'
		                              ) ) ) {
                    throw new Exception( sprintf( __( 'Could not insert family member: %s', 'gift-wish-list' ), htmlentities( $line ) ) );
                }
                $current_member = $wpdb->insert_id;
		    
                break;
		
            case "item":
                if ( count( $fields ) != 3 ) {
                    throw new Exception( sprintf( __( 'Incorrect field count for "item" keyword: %s', 'gift-wish-list' ), htmlentities( $line ) )  );
                }
                $category = $fields[1];
                $description = $fields[2];
                
                if ( $current_member == NULL ) {
                    throw new Exception( sprintf( __( 'No family member for item \'%s\'', 'gift-wish-list' ), htmlentities( $description ) ) );
                }
                if ( $current_family == NULL ) {
                    throw new Exception( sprintf( __( 'No family for item \'%s\'', 'gift-wish-list' ), htmlentities( $description ) ) );
                }
		
                if ( ! $wpdb->insert( $items_table_name,
                                      array(
                                        'family_member_id' => $current_member,
                                        'category' => $category,
                                        'description' => $description
                                      ),
                                      array(
                                        '%d',
                                        '%s',
                                        '%s'
                                      ) ) ) {
                    throw new Exception( sprintf( __( 'Could not insert wish list item: %s', 'gift-wish-list' ), htmlentities( $description ) ) );
                }
                
                break;
            default:
                throw new Exception( sprintf( __( 'Unknown keyword %s found', 'gift-wish-list' ), htmlentities( $fields[0] ) ) );
		    }
		
        }
		
        fclose( $in );
	}

	private static function get_wish_list_fields( $line )
	{
	    $out_fields = array();
        
        //  foreach ( str_getcsv( $line ) as $field )
        foreach ( $line as $field )
        {
            $value = htmlentities( trim( $field ) );
            if ( $value != "" )
            {
                $out_fields[] = $value;
            }
        }
        return $out_fields;
	}
	
	private static function setting_get($name, $default = "")
	{
		global $wpdb;
		$settings_table_name = self::get_table_name( self::TABLE_SETTINGS );

		$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table_name WHERE name = %s LIMIT 1", $name), ARRAY_A);
		if ( $result )
		{
			return $result['value'];
		}
		return $default;
	}

	private static function setting_get_all()
	{
		global $wpdb;
		$settings_table_name = self::get_table_name( self::TABLE_SETTINGS );

		$rv = array();

		$results = $wpdb->get_results("SELECT * FROM $settings_table_name", ARRAY_A);
		foreach($results as $row)
		{
			$rv[$row['name']] = $row['value'];
		}

		return $rv;
	}

	private static function setting_exists($name)
	{
		global $wpdb;
		$settings_table_name = self::get_table_name( self::TABLE_SETTINGS );

		$result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $settings_table_name WHERE name = %s LIMIT 1", $name), ARRAY_A);
		if ( $result )
		{
			return true;
		}
		return false;
	}

	private static function setting_set($name, $value, $allow_update = false)
	{
		global $wpdb;
		$settings_table_name = self::get_table_name( self::TABLE_SETTINGS );

		if (self::setting_exists($name))
		{
			if ($allow_update)
			{
				return !($wpdb->update( $settings_table_name,
								   		array('value' => $value),
								   		array('name' => $name)
								      ) === false);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return  !($wpdb->insert( $settings_table_name,
									 array('name' => $name,
									  	   'value' => $value)
								   ) === false);
		}

		return true;
	}

	#======================================================================
	# Lifecycle handlers
	#======================================================================
	
	static function activate_plugin() {
		global $wpdb;
	
		$installed_ver = get_option( self::DB_VERSION_OPTION );

		if ( $installed_ver != self::DB_VERSION ) {			
			/* IMPORTANT IMPORTANT IMPORTANT IMPORTANT IMPORTANT
			 * All DB instantiation and updates must be done using methods
			 * defined by the external wish list app.
			 */							 											
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}
	
	static function update_db_check() {
	    if ( get_site_option( self::DB_VERSION_OPTION ) != self::DB_VERSION ) {
	        self::activate_plugin();
	    }
	}
	
	static function uninstall_plugin() {
		
	}

	#======================================================================
	# Utility functions
	#======================================================================
	
	static function get_table_name( $base ) {
		global $wpdb;
		
		//return $wpdb->prefix . 'giftwishlist_' . $base;
		return $base;
	}
}


add_action('admin_menu', array( 'Gift_Wish_List', 'admin_menu') );

# Register for event when plugin is activated
register_activation_hook( __FILE__, array( 'Gift_Wish_List', 'activate_plugin' ) );
# Register for event when plugin is loaded to handle plugin updates
add_action( 'plugins_loaded', array( 'Gift_Wish_list', 'update_db_check' ) );
# Register for event when plugin is uninstalled
register_uninstall_hook( __FILE__, array( 'Gift_Wish_List', 'uninstall_plugin' ) );

?>
