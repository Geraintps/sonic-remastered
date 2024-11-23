# ![sonic](https://media.discordapp.net/attachments/373549095769341952/1309644873716203580/sonic-removebg-preview.png?ex=67425555&is=674103d5&hm=fae4b310f3d73d8837e348e86c06183d9276a70561a3085576dc5d016ee689f8&=&format=webp&quality=lossless&width=38&height=25) ᔕ𝚘𝚗𝚒𝚌 *𝚛𝚎𝚖𝚊𝚜𝚝𝚎𝚛𝚎𝚍*

> A Discord bot with PHP backend and Node.js bot functionality, containerised with Docker.

### Prerequisites

- Docker and Docker Compose installed (see [Docker installation guide](https://docs.docker.com/engine/install/))
- Discord Bot Application credentials (from [Discord Developer Portal](https://discord.com/developers/))

## Initial Setup

Clone the repository:

```bash
git clone git@github.com:Geraintps/sonic-remastered.git
cd sonic-remastered
```

Generate secure credentials for your .env file:

- For MySQL root password:
    ```bash
    openssl rand -base64 32
    ```

- For MySQL user password:
    ```bash
    openssl rand -base64 24
    ```

- For encryption key (used for PHP-Node.js communication):
    ```bash
    openssl rand -hex 32
    ```

Create a .env file in the root directory. Generally, you can copy the [.env.example](.env.example) file and update the values:

```properties
# Bot-to-PHP Communication
ENCRYPTION_KEY=your_generated_encryption_key

# Database Configuration
MYSQL_ROOT_PASSWORD=your_generated_root_password
MYSQL_DATABASE=sonic
MYSQL_USER=sonic
MYSQL_PASSWORD=your_generated_user_password

# Application Configuration
SITE_LINK=http://localhost/
ENVIRONMENT=DEVELOPMENT|PRODUCTION
DEBUG=true|false
```

## Starting the Services

Build and start the containers:
```bash
docker compose up -d --build  # omit -d to attach and see container logs live
```

Wait for Docker to show that the database container is 'healthy'. You can also check manually with:
```bash
docker compose ps
```

By default, the database will be created with a clean schema (see [migrations](migrations/)). If you have an existing database dump, you can import it as described below.

> [!NOTE]
> You can verify the database import by checking the PHPMyAdmin interface at http://localhost:8080
> Alternatively, you can use the following command to check the database:
> ```bash
> docker exec -i sonic-db /bin/bash -c 'mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE -e "SHOW TABLES;"'
> ```

### Importing an existing database dump

> [!IMPORTANT]
> These steps presume you have an SQL database dump available to load in!

```bash
docker exec -i sonic-db /bin/bash -c 'mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE' < dump.sql
```

## Discord Bot Configuration

Navigate to PHPMyAdmin:
- Open http://localhost:8080
- Login with the username `MYSQL_USER` and password `MYSQL_PASSWORD` you set in .env

Configure Discord Bot credentials:
- Go to `sonic` (or `MYSQL_DATABASE`) → `sys_settings` table
- Update the following rows:
    - `clientId`: Your Discord Bot's Client ID
    - `clientKey`: Your Discord Bot's Token

Because the bot container continually restarts, once the credentials are set the bot should automatically connect and start.

## Service Access

- Web Interface: http://localhost
- PHPMyAdmin: http://localhost:8080
- Discord Bot: Will be active once credentials are configured

## Maintenance Commands

Reset everything and start fresh:
```bash
docker compose down -v
docker compose up -d --build  
# omit -d to attach and see logs
# optionally omit --build if no changes have been made since last build
```

View logs:
```bash
docker compose logs -f
# Or for specific service
docker compose logs -f web
docker compose logs -f db
docker compose logs -f phpmyadmin
```

Generate a clean database schema for initial database structure:
```bash
# First, generate the full schema
docker exec -i sonic-db /bin/bash -c 'mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD --no-data --no-tablespaces $MYSQL_DATABASE' > migrations/V1__schema.sql

# Then get sys_settings structure
docker exec -i sonic-db /bin/bash -c 'mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD --complete-insert --no-create-info --skip-extended-insert --no-tablespaces $MYSQL_DATABASE sys_settings' > migrations/V2__sys_settings_template.sql
```

## Project Structure

The project follows a structured layout:
```
src/
├── app/  # Node.js Discord Bot
│   ├── bins.mp3
│   ├── bot.js
│   ├── Modules
│   ├── node_modules
│   └── package.json
├── config/  # PHP Configuration
│   ├── autoload.php
│   └── config.php
├── lib/  # PHP Classes
│   ├── Commands
│   └── Database.class.php
└── public/  # Web Root
    ├── .htaccess
    ├── assets
    ├── functions.php
    ├── index.php
    ├── requests
    └── robots.txt
 ```

## Security Notes

- All sensitive information is stored in .env file (not committed to repository)
- Database credentials are managed through Docker environment variables
- Encryption key is used for secure communication between PHP and Node.js

## Troubleshooting

Permission Issues:
- The containers are configured to run with proper user permissions
- Log directory is created automatically with correct ownership

Database Connection Issues:
- Ensure the database container is healthy
- Check credentials in .env file
- Verify database import was successful

Discord Bot Issues:
- Verify credentials in `sys_settings` table (*are they the wrong way around?*)
- Check container logs for error messages
- Ensure bot has proper permissions in Discord