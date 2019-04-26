<?php

namespace WP_CLI_PACKAGIST\Utility;

class Optimize {
	/**
	 * Optimize database tables
	 */
	public static function optimize_db() {
		$options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => true,
			'exit_error' => true,
		);
		\WP_CLI::runcommand( "db optimize", $options );
		\WP_CLI::runcommand( "db repair", $options );
	}

	/**
	 * Remove From wp_posts
	 *
	 * @param $field
	 * @param $value
	 * @return integer
	 */
	public static function remove_from_wp_posts( $field, $value ) {
		global $wpdb;

		$post_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE `$field` = '$value'" );
		if ( $post_count >0 ) {
			$query = $wpdb->get_results("SELECT `ID` FROM {$wpdb->posts} WHERE `$field` = '$value'", ARRAY_A);
			foreach ($query as $args) {
				$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE `post_id` = {$args['ID']}" );
				$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE `ID` = {$args['ID']}" );
			}

		}

		return $post_count;
	}

	/**
	 * Clean all post revisions
	 */
	public static function clear_post_revisions() {
		return self::remove_from_wp_posts('post_type', 'revision');
	}

	/**
	 * Clean all auto-draft posts
	 */
	public static function clean_auto_draft() {
		return self::remove_from_wp_posts('post_status', 'auto-draft');
	}

	/**
	 * Clean all trashed posts
	 */
	public static function clean_all_trashed_post(  ) {
		return self::remove_from_wp_posts('post_type', 'trash');
	}

	/**
	 * Remove From wp_comment
	 */
	public static function remove_from_wp_comments( $field, $value ) {
		global $wpdb;

		$comment_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE `$field` = $value" );
		if ( $comment_count >0 ) {
			$query = $wpdb->get_results("SELECT `comment_ID` FROM {$wpdb->comments} WHERE `$field` = $value", ARRAY_A);
			foreach ($query as $args) {
				$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE `comment_id` = {$args['comment_ID']}" );
				$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE `comment_ID` = {$args['comment_ID']}" );
			}

		}

		return $comment_count;
	}

	/**
	 * Remove spam comments
	 */
	public static function remove_spam_comments() {
		return self::remove_from_wp_comments("comment_type", "'spam'");
	}

	/**
	 * Remove trash comments
	 */
	public static function remove_trash_comments() {
		return self::remove_from_wp_comments("comment_approved", "'trash'");
	}

	/**
	 * Remove unapproved comments
	 */
	public static function remove_unapproved_comment(  ) {
		return self::remove_from_wp_comments("comment_approved", "'0'");
	}

	/**
	 * Remove All ping back comment
	 */
	public static function remove_pingback_comments() {
		return self::remove_from_wp_comments("comment_type", "'pingback'");
	}

	/**
	 * Remove All track backs
	 */
	public static function remove_trackbacks() {
		return self::remove_from_wp_comments("comment_type", "'trackback'");
	}

	/*
	 * Remove expired transient options
	 */
	public static function remove_expired_transient() {
		$options = array(
			'return'     => true,
			'parse'      => 'json',
			'launch'     => true,
			'exit_error' => true,
		);
		\WP_CLI::runcommand( "transient delete --expired", $options );
	}

	/**
	 * Clean Options
	 */
	public static function clean_options() {
		global $wpdb;

		$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `autoload` = 'yes' AND `option_name` LIKE '%jetpack%'");
		$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '_wp_session_%'");
		$wpdb->query("DELETE FROM `{$wpdb->options}` WHERE option_name LIKE '\_transient\_%' OR option_name LIKE '\_site\_transient\_%' OR option_name like 'displayed\_galleries\_%' OR option_name like 'displayed\_gallery\_rendering\_%'");
	}

	/**
	 * Clean post meta data
	 */
	public static function clean_post_meta_data() {
		global $wpdb;
		$wcu_sql = "DELETE pm FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL";
		$wpdb->query($wcu_sql);
	}

	/**
	 * Clean Comment Meta data
	 */
	public static function clean_comment_meta_data() {
		global $wpdb;
		$wcu_sql = "DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_id FROM $wpdb->comments)";
		$wpdb->query($wcu_sql);
	}

	/**
	 * Clean orphaned relationship data
	 */
	public static function clean_orphaned_data() {
		global $wpdb;
		$wcu_sql = "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM $wpdb->posts)";
		$wpdb->query($wcu_sql);
	}

}