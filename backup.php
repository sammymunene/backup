<?php
require 'vendor/autoload.php';
session_start();

// At the top of the file, after session_start():
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'auth_tokens'
];

// At the top of your file, add this with your other configurations
define('GOOGLE_DRIVE_FOLDER_ID', '1yq6GK_vN9J2IysX7vGcasyGD0GkE24tz');

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']}", 
        $db_config['username'], 
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function setMessage($message, $type = 'error') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function getGoogleClient() {
    $client = new Google_Client();
    
    // Ensure client_secret.json exists
    if (!file_exists('client_secret.json')) {
        throw new Exception('client_secret.json is missing');
    }
    
    $client->setAuthConfig('client_secret.json');
    
    // Set the redirect URI - MUST match exactly what's in Google Cloud Console
    $client->setRedirectUri('http://localhost/backup/backup.php');
    
    // OAuth configuration
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    $client->setIncludeGrantedScopes(true);
    $client->setPrompt('consent');
    
    // Add required scopes
    $client->addScope(Google_Service_Drive::DRIVE_FILE);
    $client->addScope('https://www.googleapis.com/auth/userinfo.email');
    
    return $client;
}

function saveToken($token) {
    global $pdo;
    
    try {
        // Debug output
        error_log('=== START TOKEN SAVE ===');
        error_log('Token to save: ' . json_encode($token));
        
        // Validate token structure
        if (!isset($token['access_token'])) {
            error_log('Token validation failed: missing access_token');
            throw new Exception('Token missing access_token');
        }
        
        try {
            // Clear existing tokens
            error_log('Clearing existing tokens...');
            $pdo->exec("DELETE FROM tokens");
            
            // Prepare insert
            error_log('Preparing insert statement...');
            $stmt = $pdo->prepare("INSERT INTO tokens 
                (access_token, refresh_token, expires_in) 
                VALUES (:access_token, :refresh_token, :expires_in)");
                
            $params = [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in' => $token['expires_in'] ?? 3600
            ];
            
            error_log('Executing with params: ' . json_encode($params));
            
            // Execute insert
            $result = $stmt->execute($params);
            error_log('Insert result: ' . ($result ? 'SUCCESS' : 'FAILED'));
            
            if (!$result) {
                error_log('PDO error info: ' . json_encode($stmt->errorInfo()));
                throw new Exception('Failed to execute insert');
            }
            
            // Verify save
            error_log('Verifying saved token...');
            $savedToken = loadToken();
            if (!$savedToken) {
                error_log('Verification failed - no token found');
                throw new Exception('Token save verification failed');
            }
            
            error_log('Token saved and verified successfully');
            error_log('=== END TOKEN SAVE ===');
            return true;
            
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            throw new Exception('Database error: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log('Failed to save token: ' . $e->getMessage());
        return false;
    }
}

function loadToken() {
    global $pdo;
    
    try {
        error_log('Loading token from database...');
        $stmt = $pdo->query("SELECT * FROM tokens ORDER BY id DESC LIMIT 1");
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($token) {
            error_log('Token found in database');
            $result = [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                'expires_in' => $token['expires_in'],
                'created' => strtotime($token['created_at'])
            ];
            error_log('Loaded token: ' . json_encode($result));
            return $result;
        }
        
        error_log('No token found in database');
        return null;
    } catch (Exception $e) {
        error_log('Error loading token: ' . $e->getMessage());
        return null;
    }
}

function uploadFile($client, $filePath) {
    try {
        // Create Drive service
        $service = new Google_Service_Drive($client);
        
        // Verify file
        if (!file_exists($filePath)) {
            throw new Exception('File not found: ' . $filePath);
        }
        
        // Get file details
        $fileName = basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        
        error_log('Uploading file: ' . $fileName . ' (' . $mimeType . ')');
        
        // Create file metadata with specific folder ID
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $fileName . '_' . date('Y-m-d_H-i-s'),
            'parents' => [GOOGLE_DRIVE_FOLDER_ID]
        ]);

        // Create file
        $file = $service->files->create(
            $fileMetadata,
            [
                'data' => file_get_contents($filePath),
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id,name,webViewLink',
                'supportsAllDrives' => true  // Add this if it's a shared drive
            ]
        );
        
        error_log('File uploaded successfully. ID: ' . $file->id);
        
        return [
            'success' => true,
            'message' => 'File uploaded successfully to backup folder',
            'file_id' => $file->id,
            'file_name' => $file->name,
            'web_view_link' => $file->webViewLink
        ];
        
    } catch (Exception $e) {
        error_log('Upload error: ' . $e->getMessage());
        throw new Exception('Upload failed: ' . json_encode($e->getMessage()));
    }
}

// Main logic
try {
    $client = getGoogleClient();
    
    // Handle backup request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_path'])) {
        try {
            error_log('=== START BACKUP REQUEST ===');
            
            // Load existing token
            $token = loadToken();
            
            if (!$token) {
                error_log('No token found - starting OAuth flow');
                $_SESSION['pending_file_path'] = $_POST['file_path'];
                $authUrl = $client->createAuthUrl();
                redirect($authUrl);
            }
            
            // Set the token in client
            $client->setAccessToken($token);
            
            // Only refresh if expired
            if ($client->isAccessTokenExpired()) {
                error_log('Token expired - attempting refresh');
                if (isset($token['refresh_token'])) {
                    try {
                        $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                        $newToken = $client->getAccessToken();
                        
                        // Preserve refresh token
                        if (!isset($newToken['refresh_token']) && isset($token['refresh_token'])) {
                            $newToken['refresh_token'] = $token['refresh_token'];
                        }
                        
                        saveToken($newToken);
                    } catch (Exception $e) {
                        error_log('Token refresh failed - starting new OAuth flow');
                        $_SESSION['pending_file_path'] = $_POST['file_path'];
                        redirect($client->createAuthUrl());
                    }
                } else {
                    error_log('No refresh token - starting new OAuth flow');
                    $_SESSION['pending_file_path'] = $_POST['file_path'];
                    redirect($client->createAuthUrl());
                }
            }

            // Process the file upload
            $filePath = $_POST['file_path'];
            
            if (!file_exists($filePath)) {
                throw new Exception('File not found: ' . $filePath);
            }
            
            if (!is_readable($filePath)) {
                throw new Exception('File is not readable: ' . $filePath);
            }
            
            // Upload file
            $result = uploadFile($client, $filePath);
            
            // Return success
            setMessage('File uploaded successfully! View it here: ' . $result['web_view_link'], 'success');
            redirect('index.php');
            
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            setMessage($e->getMessage());
            redirect('index.php');
        }
    }
    
    // Handle OAuth callback
    if (isset($_GET['code'])) {
        try {
            error_log('=== START OAUTH CALLBACK ===');
            
            // Exchange code for token
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            
            if (isset($token['error'])) {
                throw new Exception('Failed to get token: ' . $token['error']);
            }
            
            // Save the token
            if (!saveToken($token)) {
                throw new Exception('Failed to save token');
            }
            
            // If we have a pending upload, process it
            if (isset($_SESSION['pending_file_path'])) {
                $filePath = $_SESSION['pending_file_path'];
                unset($_SESSION['pending_file_path']);
                
                // Submit the file through POST
                echo '
                <form id="uploadForm" action="backup.php" method="post">
                    <input type="hidden" name="file_path" value="' . htmlspecialchars($filePath) . '">
                </form>
                <script>document.getElementById("uploadForm").submit();</script>
                ';
                exit;
            }
            
            setMessage('Authentication successful!', 'success');
            redirect('index.php');
            
        } catch (Exception $e) {
            error_log('OAuth error: ' . $e->getMessage());
            setMessage('Authentication failed: ' . $e->getMessage());
            redirect('index.php');
        }
    }
    
} catch (Exception $e) {
    error_log('Error in backup.php: ' . $e->getMessage());
    setMessage($e->getMessage());
    redirect('index.php');
}

// If we get here without a POST request, redirect to index
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
} 