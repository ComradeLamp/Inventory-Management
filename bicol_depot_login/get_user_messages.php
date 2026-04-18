<?php
session_start();

//Added proper error handling and debugging
ini_set('display_errors', 0); // Don't show errors to users
error_reporting(E_ALL); // Log all errors

//Log file for debugging ???
$logFile = __DIR__ . '/message_errors.log';

function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

//Set JSON header
header('Content-Type: application/json');

try {
    //Check auth
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        //Forbidden status if not admin
        http_response_code(403);
        throw new Exception('Access denied. Admin privileges required.');
    }

    //Check if user_id paramiter exists
    if (!isset($_GET['user_id'])) {
        throw new Exception('Missing user ID parameter');
    }

    //Get & validate user ID from request
    $userId = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : 0;

    if ($userId <= 0) {
        throw new Exception('Invalid user ID');
    }

    //Include db connection
    require_once 'db.php';
    
    //Verify connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Connection variable not set'));
    }

    //Initialize messages array
    $messages = [];

    //Check if messages table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
    
    if (!$tableCheck) {
        throw new Exception("Failed to check for messages table: " . $conn->error);
    }
    
    if ($tableCheck->num_rows == 0) {
        //IF Table doesn't exist, return empty result instead of error
        echo json_encode([]);
        exit();
    }
    
    //IF Tbale exist - Check structure to determine column names
    $columnQuery = "DESCRIBE messages";
    $columnResult = $conn->query($columnQuery);
    
    if (!$columnResult) {
        throw new Exception("Failed to get table structure: " . $conn->error);
    }
    
    //Check which columns exist in the table
    $hasSubject = false;
    $hasTitle = false;
    $hasContent = false;
    $hasMessage = false;
    $hasUserId = false;
    $columns = [];
    
    while ($row = $columnResult->fetch_assoc()) {
        $columns[] = $row['Field'];
        if ($row['Field'] == 'subject') $hasSubject = true;
        if ($row['Field'] == 'title') $hasTitle = true;
        if ($row['Field'] == 'content') $hasContent = true;
        if ($row['Field'] == 'message') $hasMessage = true;
        if ($row['Field'] == 'user_id') $hasUserId = true;
    }
    
    //Check if user_id column exists
    if (!$hasUserId) {
        throw new Exception("Required column 'user_id' not found in messages table");
    }
    
    //Determine which column names to use
    $subjectColumn = $hasSubject ? 'subject' : ($hasTitle ? 'title' : 'id');
    $messageColumn = $hasContent ? 'content' : ($hasMessage ? 'message' : 'user_id');
    
    //Prepare the query to fetch only the recent message
    $msgQuery = "SELECT m.id, m.user_id, 
                m.$subjectColumn as subject_field, 
                m.$messageColumn as content_field, 
                m.created_at 
                FROM messages m
                WHERE m.user_id = ?
                ORDER BY m.created_at DESC
                LIMIT 3";  //Limit to 3 message, most recent.
    
    $stmt = $conn->prepare($msgQuery);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    //Process results
    if ($row = $result->fetch_assoc()) {
        if (in_array('is_read', $columns) && isset($row['id'])) {
            try {
                $updateQuery = "UPDATE messages SET is_read = 1 WHERE id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                
                if ($updateStmt) {
                    $updateStmt->bind_param("i", $row['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            } catch (Exception $e) {
                //Log but continue ?????????????????????? Ano?
                logError("Failed to mark message as read: " . $e->getMessage());
            }
        }
        
        $messages[] = [
            'id' => $row['id'],
            'subject' => htmlspecialchars($row['subject_field'] ?? ''),
            'content' => nl2br(htmlspecialchars($row['content_field'] ?? '')),
            'created_at' => isset($row['created_at']) ? 
                date('F d, Y - h:i A', strtotime($row['created_at'])) : 
                date('F d, Y - h:i A')
        ];
    }
    
    $stmt->close();
    
    //Return t0 messages
    echo json_encode($messages);

} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    //Close db connection
    if (isset($conn)) $conn->close();
}