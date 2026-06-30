<?php

define('ABSPATH', __DIR__ . '/../');

if (! class_exists('WP_Post')) {
	class WP_Post
	{
		public int $ID = 0;
		public string $post_status = '';
		public string $post_type = '';
		public string $post_password = '';
	}
}

require_once __DIR__ . '/../includes/class-mdai-bot-detector.php';
require_once __DIR__ . '/../includes/class-mdai-analytics.php';
require_once __DIR__ . '/../includes/class-mdai-rest.php';
require_once __DIR__ . '/../includes/admin/class-mdai-admin.php';
