<VirtualHost *:80>
	ServerAdmin webmaster@localhost

	DocumentRoot /var/www/

	<Directory />
		Options FollowSymLinks
		AllowOverride None
	</Directory>
	<Directory /var/www/>
		Options FollowSymLinks MultiViews
		AllowOverride ALL
		Order allow,deny
		Allow from all
	</Directory>

	AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript text/xml application/x-javascript application/javascript
	BrowserMatch ^Mozilla/4 gzip-only-text/html
	BrowserMatch "MSIE 6" no-gzip dont-vary
	BrowserMatch ^Mozilla/4\.0[678] no-gzip

	# Oplossing voor fontfile probleem header?
	AddType application/vnd.ms-fontobject .eot
	AddType application/octet-stream .ttf .otf .woff
	<FilesMatch "\.(ttf|woff|otf|eot)$">
		<IfModule mod_headers.c>
			Header set Access-Control-Allow-Origin "*"
		</IfModule>
	</FilesMatch>

	ErrorLog ${APACHE_LOG_DIR}/error.log

	# Possible values include: debug, info, notice, warn, error, crit,
	# alert, emerg.
	LogLevel debug

	CustomLog ${APACHE_LOG_DIR}/access.log combined

</VirtualHost>

