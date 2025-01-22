# Google Drive Backup System

A PHP-based system for backing up files to a specific Google Drive folder using OAuth 2.0 authentication.

## Features
- Web interface for file uploads
- OAuth 2.0 authentication with Google Drive
- Persistent authentication using database storage
- Automatic token refresh
- Uploads to a specific Google Drive folder
- Dark mode UI

## Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Composer
- XAMPP/LAMPP or similar web server
- Google Cloud Console project with Drive API enabled

## Installation

1. Clone the repository to your web server directory:
```bash
cd /opt/lampp/htdocs
git clone <repository-url> backup
cd backup
```

2. Install dependencies using Composer:
```bash
composer require google/apiclient:^2.0
```

3. Set up the database:
```sql
CREATE DATABASE auth_tokens;
USE auth_tokens;

CREATE TABLE tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    expires_in INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

4. Configure Google Cloud Console:
   - Go to Google Cloud Console
   - Create a new project or select existing one
   - Enable the Google Drive API
   - Configure OAuth consent screen:
     - Set User Type to "External"
     - Add your email as a test user
   - Create OAuth 2.0 credentials:
     - Application type: Web application
     - Add authorized redirect URI: `http://localhost/backup/backup.php`
   - Download the client configuration file as `client_secret.json`
   - Place `client_secret.json` in the project root directory

5. Configure the application:
   - Update the folder ID in `backup.php`:
     ```php
     define('GOOGLE_DRIVE_FOLDER_ID', 'your_folder_id_here');
     ```
   - Update database credentials in `backup.php` if needed

## Usage

### Web Interface
1. Access the application:
```
http://localhost/backup/index.php
```

2. Enter the full path to the file you want to backup
3. Click "Backup to Google Drive"
4. First time: Complete Google authentication
5. File will be uploaded to the specified Google Drive folder

### Testing with Postman/Insomnia
While the application is designed with a web interface, you can test the API endpoints:

1. POST Request:
```
URL: http://localhost/backup/backup.php
Method: POST
Body (form-data):
  - file_path: /path/to/your/file.txt
```

2. The response will be JSON:
```json
{
    "success": true,
    "message": "File uploaded successfully to backup folder",
    "file_id": "1abc...",
    "file_name": "test.txt_2024-03-14_12-34-56",
    "web_view_link": "https://drive.google.com/file/d/..."
}
```

## File Structure
- `index.php` - Web interface
- `backup.php` - Main application logic
- `setup_db.php` - Database setup script
- `test_db.php` - Database connection test
- `client_secret.json` - Google OAuth credentials

## Error Handling
- Check PHP error logs:
```bash
tail -f /opt/lampp/logs/php_error.log
```

- Common issues:
  - Missing client_secret.json
  - Invalid folder ID
  - Database connection issues
  - File permissions

## Security Notes
- Keep your client_secret.json secure
- Use HTTPS in production
- Properly configure file permissions
- Validate file paths before upload

## Development
- Test database connection: `http://localhost/backup/test_db.php`
- Test OAuth flow: `http://localhost/backup/test_auth.php`
- Check token storage: `http://localhost/backup/check_secret.php`

## License
MIT
