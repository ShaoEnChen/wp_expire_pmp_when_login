<?php
/* Below is edited by Sean, Dec. 13, 2016 */
/**
 * Custom: expire user's pmp membership when login.
 */
function expire_pmp_membership( $user_login = null, $user = null ) {
	// get database
	global $wpdb;

	if( !is_null($user_login) && !is_null($user) ) {
		// get current user's id for query
		$user_id = $user->ID;

		// get pmp membership of current user
		$sql_query = "SELECT * FROM $wpdb->pmpro_memberships_users mu WHERE mu.status = 'active' AND mu.user_id = " . $user_id;
		$pmp_user = $wpdb->get_row($sql_query);

		if( !is_null($pmp_user) ) {
			// get current time (String)
			$now = date_i18n("Y-m-d H:i:s", current_time("timestamp"));

			// get current user's enddate (String)
			$enddate = $pmp_user->enddate;

			// String to DateTime
			$now_date = date_create($now);
			$enddate_date = date_create($enddate);

			// expire user's membership if expire date is not Never & expire date is dued
			if( ( $enddate != "0000-00-00 00:00:00" ) && ( $enddate_date < $now_date ) ) {
				do_action("pmpro_membership_pre_membership_expiry", $pmp_user->user_id, $pmp_user->membership_id );

				// set his membership to id 9: Not-Yet-Paid
				pmpro_changeMembershipLevel(9, $pmp_user->user_id);

				do_action("pmpro_membership_post_membership_expiry", $pmp_user->user_id, $pmp_user->membership_id );

				$send_email = apply_filters("pmpro_send_expiration_email", true, $pmp_user->user_id);
				if($send_email) {
					//send an email
					$pmproemail = new PMProEmail();
					$euser = get_userdata($pmp_user->user_id);
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
	}
}
add_action( 'wp_login', 'expire_pmp_membership', 10, 2 );

/**
 * Custom: keep members logged in for 1 hour in case their memberships expire.
 */
function keep_member_logged_in_for_1_hour( $expirein ) {
	return 3600; // 1 hour in seconds, 60 * 60
}
add_filter( 'auth_cookie_expiration', 'keep_member_logged_in_for_1_hour' );

/**
 * Custom: Add pmp membership "Not-Yet-Paid" to new-registered user
 */
function default_pmp_member_level( $user_id ) {
	pmpro_changeMembershipLevel(9, $user_id);
}
add_action( 'user_register', default_pmp_member_level );
