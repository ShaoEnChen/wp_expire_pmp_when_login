/* Below is edited by Sean, Dec. 13, 2016 */
/**
 * Custom: expire user's pmp membership when log in.
 */
<?php
function expire_pmp_membership( $user_login = NULL, $user = NULL ) {
	// get database
	global $wpdb;

	if( !is_null($user_login) && !is_null($user) ) {
		// get current user's id for query
		$cu_id = $user->ID;

		// get pmp membership of current user
		$sql_query = "SELECT * FROM $wpdb->pmpro_memberships_users mu WHERE mu.status = 'active' AND mu.user_id = " . $cu_id;
		$expired_user = $wpdb->get_row($sql_query);

		// get current time (String)
		$now = date_i18n("Y-m-d H:i:s", current_time("timestamp"));

		// get current user's enddate (String)
		$enddate = $expired_user->enddate;

		echo "<script>alert('$now');</script>";
		echo "<script>alert('$enddate');</script>";

		$now_date = date_create($now);
		$enddate_date = date_create($enddate);

		if( $now_date > $enddate_date ) {
			do_action("pmpro_membership_pre_membership_expiry", $expired_user->user_id, $expired_user->membership_id );

			//remove their membership
			pmpro_changeMembershipLevel(false, $expired_user->user_id, 'expired');

			do_action("pmpro_membership_post_membership_expiry", $expired_user->user_id, $expired_user->membership_id );

			$send_email = apply_filters("pmpro_send_expiration_email", true, $expired_user->user_id);
			if($send_email) {
				//send an email
				$pmproemail = new PMProEmail();
				$euser = get_userdata($expired_user->user_id);
				$pmproemail->sendMembershipExpiredEmail($euser);

				if(current_user_can('manage_options'))
					printf(__("Membership expired email sent to %s. ", "pmpro"), $euser->user_email);
				else
					echo ". ";
			}
		}
		else {
			// not expired yet, continue redirect
		}
	}
	exit;
}
add_action( 'wp_login', 'expire_pmp_membership', 10, 2 );

/**
 * Custom: keep members logged in for 1 hour in case their memberships expire.
 */
function keep_member_logged_in_for_1_hour( $expirein ) {
    return 86400; // 1 hour in seconds
}
add_filter( 'auth_cookie_expiration', 'keep_me_logged_in_for_1_hour' );
