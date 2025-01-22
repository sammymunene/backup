<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Drive Backup</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #0a192f;
            --bg-secondary: #112240;
            --text-primary: #ccd6f6;
            --text-secondary: #8892b0;
            --accent: #64ffda;
            --error: #ff6b6b;
            --success: #64ffda;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background-color: var(--bg-secondary);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--accent);
            font-size: 2.5rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--text-secondary);
            border-radius: 6px;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(100, 255, 218, 0.2);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background-color: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background-color: rgba(100, 255, 218, 0.1);
            transform: translateY(-2px);
        }

        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 6px;
            font-weight: 500;
        }

        .success {
            background-color: rgba(100, 255, 218, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .error {
            background-color: rgba(255, 107, 107, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }

        .icon {
            font-size: 1.2rem;
        }

        .file-info {
            margin-top: 2rem;
            padding: 1rem;
            background-color: var(--bg-primary);
            border-radius: 6px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .file-info p {
            margin: 0.5rem 0;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-cloud-upload-alt"></i> Google Drive Backup</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <i class="icon fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <form action="backup.php" method="post">
            <div class="form-group">
                <label for="file_path">
                    <i class="fas fa-file-alt"></i> File Path
                </label>
                <input type="text" id="file_path" name="file_path" 
                       placeholder="/path/to/your/file.txt" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-cloud-upload-alt"></i>
                Backup to Google Drive
            </button>
        </form>

        <div class="file-info">
            <p><i class="fas fa-info-circle"></i> The file will be uploaded to your Google Drive root folder.</p>
            <p><i class="fas fa-clock"></i> A timestamp will be added to the filename.</p>
            <p><i class="fas fa-shield-alt"></i> Your file will be securely transferred using OAuth 2.0.</p>
        </div>
    </div>
</body>
</html> 