<?php
/**
 * @package Add_Multiple_Users
 * @version 2.0.0
 * Network Options and Add Existing functions
 */

//protect from direct call
if ( !function_exists( 'add_action' ) ) {
	echo "Access denied!";
	exit;
}

/*
	* ADD EXISTING USERS TO SITE INTERFACE AND FUNCTION
	* interface to add users to site from network list
*/
function amu_add_help_tab(){
	ob_start();
	?>
	            <h3><?php _e('Information about Adding Existing Users','amulang'); ?></h3>
            <p><?php _e('On this page you will see a list of users taken from your Network list who are NOT already a part of this site. Firstly, set the two options as desired above the user list. You may then check the users you wish to add to this site and click the Add All Users button.','amulang'); ?></p>
            <h4><?php _e('Options for Adding Existing Users','amulang'); ?></h4>
            <p><strong><?php _e('Ignore individual roles and set all selected users to this role','amulang'); ?>:</strong> <br />
                <?php _e('You can assign each existing user you add to this site an individual Role within this site. Make a selection here if you want to add all existing users you choose with the Role defined here instead.','amulang'); ?></p>
            <p><strong><?php _e('Send each user a confimation email','amulang'); ?>:</strong> <br />
                <?php _e('If you leave this unchecked, users you select will be automatically added to this site. Check this option if you do not want this to happen. Instead, each user you select will be sent an email asking them to confirm their adding to this site. When they have confirmed, they will show up in the Users list for this site.','amulang'); ?></p>
	<?php
	$content = ob_get_contents();
	ob_end_clean();
	
	$screen = get_current_screen();
	
	$screen->add_help_tab( array( 
	   'id' => 'help-add-users',            //unique id for the tab
	   'title' => 'Help',      //unique visible title for the tab
	   'content' => $content,  //actual help text
	  
	) );
}

/**
 * amu_add_from_net function.
 * 
 * @access public
 * @return void
 */
function amu_add_from_net() {
	//test again for admin priviledges
	if (!current_user_can('manage_options') )  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	//begin wrap class
	echo '<div class="wrap">';
		echo '<div id="amu">';
		
			echo '<h2>'.__('Add Multiple Users From Network','amulang').'</h2>';
		
			//if no post made, show interface and helpers
			if (empty($_POST) || wp_verify_nonce( $_POST['nonce'], 'choose site' ) ) {
				
				amu_show_network_users();
				
			} else if ( isset($_POST['addexistingusers'] ) && wp_verify_nonce( $_POST['nonce'],  $_POST['b'].'primary-blog-nonce' ) ) {
				
				amu_add_network_users();
				
			//else throw error
			} else {
				echo '<p>'.__('Unknown request. Please select the Add from Network option to try again.','amulang').'<p>';
			}
			
		echo '</div>';
	echo '</div>';
	
}

/**
 * amu_add_network_users function.
 * 
 * @access public
 * @return void
 */
function amu_add_network_users() {
				
	global  $blog_id;
	$mainsite = SITE_ID_CURRENT_SITE;
	//times it should loop based on highest user's id
	
	
	//set overall role value
	$allExistingToRole = $_POST['existingToRole'];
	
	echo '<h3>'.__('Results of your new user registrations','amulang').'</h3>';
	
	if( isset($_POST['user_id']) && is_array($_POST['user_id'])):
		foreach($_POST['user_id'] as $user_id ):
			// set user role.
			if( $_POST['existingToRole'] == 'notset' && isset($_POST['setrole'][$user_id])) {
				$user_role = $_POST['setrole'][$user_id];
			} else {
				$user_role = $_POST['existingToRole'];
			}
			
			$user_details = get_userdata( $user_id );
			
			// add user or send them an email 
			if (isset($_POST['notifyExistingUser']) ):
			
				$newuser_key = substr( md5( $blog_id.$user_details->ID ), 0, 10 );
				add_option( 'new_user_' . $newuser_key, array( 
										'user_id' => $user_details->ID, 
										'email' => $user_details->user_email, 
										'role' => $user_role ) );
				$message = __( 'Hi,
	
You\'ve been invited to join \'%1$s\' at
%2$s with the role of %3$s.

Please click the following link to confirm the invite:
%4$s','amulang' );


				wp_mail( $user_details->user_email, sprintf( __( '[%s] Joining confirmation' ), get_option( 'blogname' ) ),  sprintf( $message, get_option('blogname'), site_url(), $user_role, site_url("/newbloguser/$newuser_key/") ) );
				//notification line
				echo '<p>'.__('User','amulang').' '.$user_details->user_login.' '.__('has been sent a confirmation email.','amulang').'</p>';
			
			else:
				// add the user right away
				add_existing_user_to_blog( array( 'user_id' => $user_id, 'role' => $user_role ) );
				
				echo '<p>'.__('User','amulang').' '.$user_details->user_login.' '.__('has been added to the site.','amulang').'</p>';
			endif;
			
		endforeach;
	endif;
}

/*
	* SHOW NETWORK USERS
	* interface to select users to add to site
*/

function amu_show_network_users() {
	global $blog_id, $wp_roles;
	
	$current_blog_id = $blog_id;
	?>
	<form method="post" enctype="multipart/form-data" class="amuform">
		
		<h3>1. Choose the site from where you want to add users from.</h3>
		<?php 
		
		$all_blogs = get_blogs_of_user( get_current_user_id() );
		
		
		$request_blog_id = ( isset($_REQUEST['pb']) ? (int)$_REQUEST['pb'] : false );
		if ( count( $all_blogs ) > 1 ) {
			$found = false;
			
			?>
			<select name="pb">
				<?php foreach( (array) $all_blogs as $blog ):
					
					switch_to_blog( $blog->userblog_id );
					
					//only allow the 
					//if(isset($current_user_array["wp_".$blog->userblog_id."_capabilities"]['administrator']) ):
					if( current_user_can('list_users') && $blog->userblog_id != $current_blog_id ) :
						$blog_ids[] = $blog->userblog_id;
						
						if( $request_blog_id == $blog->userblog_id )
							$amu_current_blog = $blog;
						
						
						?><option value="<?php echo $blog->userblog_id ?>" <?php selected($blog->userblog_id, $request_blog_id );?> ><?php echo esc_url( get_home_url( $blog->userblog_id ) ); ?> </option><?php
						endif;
					restore_current_blog();
				endforeach; ?>
			</select>
		<?php } ?>
		<input type="hidden" value="<?php echo wp_create_nonce( 'choose site' ); ?>" name="nonce" />
		<input type="submit" value="Choose Site" class="button" />	
		<br />
	</form>
	<!-- end of choose from -->
	<?php
	
	if( $request_blog_id != false && wp_verify_nonce( $_REQUEST['nonce'], 'choose site' ) && in_array( $request_blog_id, $blog_ids ) ):
	
	
	echo '<form method="post" enctype="multipart/form-data" class="amuform">';
	$amd_current_site_info = get_current_site_name($amu_current_blog);
	
	echo '<input type="hidden" name="b" value="'.esc_attr( $request_blog_id ).'" />';
	echo '<input type="hidden" value="'.wp_create_nonce( $request_blog_id.'primary-blog-nonce' ).'" name="nonce" />';
		//get this blogs id
		
		$mainsite = SITE_ID_CURRENT_SITE;
		$check_capabilities = 'wp_'.$blog_id.'_capabilities';		

		echo '<h3>2. '.__('Add User from','amulang').' <em>'.$amd_current_site_info->blogname.'</em> ('.$amd_current_site_info->siteurl.')</h3>';
		
		$offset = (isset($_GET['offset']) ? (int) $_GET['offset'] : 0);
		$number = ( ( isset($_GET['number']) && $_GET['number'] < 20 ) ? (int) $_GET['number'] : 10);
		//show users list
		//$number = 10;
		
		$args = array( 'offset' => $offset, 'number'=> $number, 'exclude'=> get_current_user_id());
		
		switch_to_blog( $request_blog_id );
		
		$allusers = get_users( $args );
		
		$total_users = count_users();
		
		restore_current_blog();
		
	
		$roles = $wp_roles->get_names();
		//set all users to this role?
		echo '<div class="optionbox">';
		
		
		echo '	<p><label for="existingToRole">'.__('Ignore individual roles and set all selected users to this role','amulang').': </label><br />';
		echo '	<select name="existingToRole" id="existingToRole">';
		echo '		<option value="notset" >'.__( 'no, set individually...','amulang').'</option>';

			foreach($roles as $role) {
				
				echo '<option value="'.strtolower($role).'" >'.$role.'</option>';
			}
		echo '	</select>
		</p>';
		echo '</div>';
		
		//username strict validation option...
		echo '<div class="optionbox">';
		echo '	<input name="notifyExistingUser" id="notifyExistingUser" type="checkbox" value="sendnotification" />';
		echo '	<label for="notifyExistingUser">'.__('Send each user a confirmation email?','amulang').' <br /><em>('.__('if selected, sends user standard WordPress confirmation email, which then need to accept before being added to the site.','amulang').')</em></label>';
		
		echo '</div>';
		
		//end multisite options wrap
		
		
		
		echo '	<h3><strong>'.__('Select network users to add to this site','amulang').':</strong></h3>';
		
		
		$total_users = $total_users['total_users'];
		$next_offset = $offset+$number;
		$previous_offset = $offset-$number;
		?>
		<div class="tablenav top">
			<div class="tablenav-pages">
				<span class="displaying-num" alt="This number might not be accurate">maximum <?php echo ($total_users-1); ?> users</span>
				<span class="pagination-links">
		<?php
		if( $offset > 0 ): 
			
			$previous_link = admin_url( 'users.php?page=amuaddfromnet&offset='.($previous_offset).'&pb='.$request_blog_id.'&nonce='.$_REQUEST['nonce'] ); 
			?>
			<a href="<?php echo $previous_link; ?>" title="Go to the previous page"  class="prev-page ">« previous page</a>
			<?php 
		
		endif; 
		
		if( ( $total_users-1 ) > $next_offset ):
		
			$next_link = admin_url( 'users.php?page=amuaddfromnet&offset='.($next_offset).'&pb='.$request_blog_id.'&nonce='.$_REQUEST['nonce'] );
			?>
			<a href="<?php echo $next_link; ?>" title="Go to the next page" class="next-page">next page »</a> 
			<?php 
			
		endif;
		
		?>
				</span>
			</div>	
			<br class="clear">
		</div>
		<?php ob_start(); ?>
		
		<table cellspacing="0" class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
			<th class="manage-column column-cb check-column" id="cb" scope="col">
				<label for="cb-select-all-1" class="screen-reader-text">Select All Users</label>
				<input type="checkbox" id="cb-select-all-1">
			</th>
			<th class="manage-column column-title sortable" id="title" scope="col">Name</th>
			<th class="manage-column column-author" id="email" scope="col">Email</th>
			<th class="manage-column column-categories" id="role" scope="col">Role</th>
		</tr>
		</thead>
	
		<tfoot>
		<tr>
			<th class="manage-column column-cb check-column" id="cb" scope="col">
				<label for="cb-select-all-1" class="screen-reader-text">Select All Users</label>
				<input type="checkbox" id="cb-select-all-1">
			</th>
			<th class="manage-column column-title sortable" id="title" scope="col">Name</th>
			<th class="manage-column column-author" id="email" scope="col">Email</th>
			<th class="manage-column column-categories" id="role" scope="col">Role</th>
		</tr>
		</tfoot>
		<tbody id="the-list">

			<?php 
			
			//show user rows
			$usertotal = 0;
			foreach ( $allusers as $user ) {
				
				//if on subsite
				if(!get_user_meta($user->ID, $check_capabilities) ) {
				?>
				<tr valign="top" >
					<th class="check-column" scope="row">
						<label for="cb-select-<?php echo $user->ID;?>" class="screen-reader-text">Select User</label>
						<input name="user_id[]" id="cb-select-<?php echo $user->ID;?>" type="checkbox" value="<?php echo $user->ID;?>" />
						
					</th>
					<td><?php echo get_avatar( $user->ID, 32 ); ?> <?php echo $user->user_login; ?></td>
					<td><?php echo $user->user_email; ?> </td>
					<td><?php echo '	<select name="setrole['.$user->ID.']" id="setrole_'.$user->ID.'">';
							foreach($roles as $role) {
								$thisrole = $role;
								echo '<option value="'.strtolower($thisrole).'">'.$thisrole.'</option>';
							}
							echo '	</select>';
							?>
					</td>
				</tr>
						<?php
				
						$usertotal++;
				}
				
			}
			$users_table = ob_get_contents(); 
			ob_end_clean();
			if( $usertotal == 0 ) {
				
				echo '<div class="toolintro">';
				echo '<p class="amu_error">'.__( 'All users on <em>'.$amd_current_site_info->blogname.'</em> ('.$amd_current_site_info->siteurl.') are already assigned a role on this site.','amulang' ).'</p>';
				echo '</div>';
			} else {
				echo $users_table; ?>
				</tbody>
				</table>
			
				
				<br />
				<div class="buttonline clear">
					<input type="submit" name="addexistingusers" class="button-primary" value="<?php _e( 'Add Selected Users','amulang' ); ?>" />
				</div>
				
		<?php
			}
		?>
			
				
		
	</form>
	<?php
	endif;
			
}
