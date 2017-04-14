# websub.rocks

WebSub test suite and debugging utility.

You can test your Publisher, Subscriber and Hubs using websub.rocks. 

Configuring websub.rocks to run locally can be a bit of a challenge, so here are some resources you may find useful to test your local services against the public websub.rocks.

* https://ngrok.com/ - Give your localhost service a public internet address
* [How to use SSH tunnels](http://www.augustcouncil.com/~tgibson/tutorial/tunneling_tutorial.html)

## Installation Instructions

### Dependencies

* PHP 5.6 or 5.7
* MySQL 5.5 or newer
* [Redis](https://redis.io)
* [nginx](http://nginx.org)
* [nginx push stream module](https://github.com/wandenberg/nginx-push-stream-module)

### Instructions

You can either install websub.rocks from git, or download a zip file from the list of [releases](https://github.com/aaronpk/websub.rocks/releases).

#### Installation from Git

Requires installing dependencies via [Composer](https://getcomposer.org/)

```
git clone git@github.com:aaronpk/websub.rocks.git
cd websub.rocks
composer install
```

#### Installation from zip release

Contains all dependencies already packaged.

* Download latest release
* Extract to a folder on your computer

#### Configure websub.rocks

Copy the `lib/config.template.php` file to `lib/config.php` and fill it out. You'll need to set the following:

* `$base` - the full base URL where you've installed websub.rocks, e.g. `http://websubrocks.example.com/`
* `$redis` - configure the host and port to your Redis instance, e.g. `tcp://127.0.0.1:6379`
* `$dbhost` and others - configure the name, host, username and password for your MySQL database
* `$skipauth` - set to `true` to bypass emailing login links
* `$secret` - set to a random string, used for signing tokens
* `$mailtun` - if you want websub.rocks to email login URLs, configure your Mailgun account info here

#### Install Redis

Install [Redis](https://redis.io/) however is appropriate for your platform. The default configuration is fine.

#### Install MySQL

Install MySQL, and create a new database called `websubrocks`. 

#### Create the Database

The database schema is in the `database/` folder, so you can set up the database with the following command:

```
mysql -u root websubrocks < database/schema.sql
```

#### Install Nginx

Websub.rocks requires nginx compiled with the [nginx push stream module](https://github.com/wandenberg/nginx-push-stream-module) to enable realtime features of the tool. 

You will need to build nginx from source in order to include the module. Feel free to follow any nginx tutorial to install it on your platform. The instructions should look more or less like the below.

* download nginx source from http://nginx.org
* git clone git@github.com:wandenberg/nginx-push-stream-module.git
* ./configure --prefix=/usr/local/nginx --add-module=../nginx-push-stream-module --with-http_v2_module
* make -j 4
* sudo make install

#### Nginx Configuration

You'll need to modify your nginx configuration to include the following.

```
http {
  # your existing defaults are probably fine

  # Set the php pool to where you have configured php-fpm to run.
  # Note this may also be a socket instead of a port.
  upstream php-pool {
    server 127.0.0.1:9000;
  }

  push_stream_shared_memory_size 32M;
}

server {
  listen       80;
  server_name  websubrocks.dev;

  root /path/to/websub.rocks/public;

  # index.php handles all requests that aren't static files
  location / {
    try_files $uri /index.php?$args;
  }

  location ~ \.php$ {
    fastcgi_pass    php-pool;
    fastcgi_index   index.php;
    include fastcgi_params;
    fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }

  # These are the push-stream streaming endpoints for publishing and subscribing

  location /streaming/pub {
    push_stream_publisher admin;
    push_stream_channels_path    $arg_id;
  }

  location /streaming/sub {
    add_header 'Access-Control-Allow-Origin' '*';
    push_stream_subscriber eventsource;
    push_stream_channels_path    $arg_id;
    push_stream_message_template                "{\"id\":~id~,\"channel\":\"~channel~\",\"text\":~text~}";
    push_stream_ping_message_interval           10s;
  }
}
```
