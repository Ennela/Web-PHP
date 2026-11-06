# Shared application routing and file protections for HTTP and HTTPS listeners.
(app) {
	root * {$APP_ROOT:.}

	header {
		-Server
		-X-Powered-By
		X-Content-Type-Options nosniff
		X-Frame-Options SAMEORIGIN
		Referrer-Policy strict-origin-when-cross-origin
		Permissions-Policy "camera=(), microphone=(), geolocation=()"
	}

	route {
		request_body {
			max_size 20MB
		}
		@trace method TRACE TRACK CONNECT
		respond @trace "Method not allowed." 405

		# Paths are normalized/decoded by Caddy before matching. Sensitive material
		# never reaches PHP or file_server; query strings cannot bypass this rule.
		@private path_regexp private (?i)(^|/)(\.[^/]+|database|documents|deploy|scripts|scratch|backups?|secrets|certs|vendor|lib|node_modules|includes|vnpay_php)(/|$)|(^|/)(config\.php|connect_db\.php|function\.php|pagination\.php|Dockerfile|Caddyfile|docker-entrypoint\.sh|composer\.(json|lock)|package(-lock)?\.json|README[^/]*)(/|$)|\.(sql|log|ini|conf|ya?ml|toml|sh|bak|backup|old|orig|save|swp|pem|key|p12|pfx)([./]|$)|~$
		respond @private "Not found." 404

		# Uploads are raster images only. PHP/script/HTML/SVG files cannot execute
		# or be rendered from either writable directory, including PATH_INFO.
		@uploadScript path_regexp uploadScript (?i)^/(admin|auth)/uploads/.*\.(php[0-9]*|phtml|pht|phar|cgi|pl|py|rb|sh|shtml|html?|svg)([./]|$)
		respond @uploadScript "Not found." 404
		@nonImageUpload {
			path /admin/uploads/* /auth/uploads/*
			not path_regexp image (?i)\.(png|jpe?g|gif|webp|avif|ico|bmp)$
		}
		respond @nonImageUpload "Not found." 404

		# Explicit entry points and directory indexes; no arbitrary front-controller
		# fallback that would turn a blocked or missing resource into a 200 page.
		php_server {
			try_files {path} {path}/index.php
		}
	}
}
