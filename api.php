<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

function getDatabaseConnection() {
    $host = '127.0.0.1';
    $db = 'sky_survey_database';
    $user = 'root';
    $pass = '8520';
    $charset = 'utf8mb4';
    $port = 3306;

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'get_questions':
                    handleGetQuestionsRequest();
                    break;
                case 'fetch_responses':
                    handleFetchResponsesRequest();
                    break;
                case 'download_certificate':
                    handleDownloadCertificateRequest();
                    break;
                case 'get_all_responses':
                    handleGetAllResponsesAndQuestions();
                    break;
                default:
                    echo json_encode(['error' => 'Invalid action']);
                    http_response_code(400);
                    break;
            }
        } else {
            echo json_encode(['error' => 'Action parameter is required']);
            http_response_code(400);
        }
        break;
    case 'POST':
        if (isset($_POST['action']) && $_POST['action'] == 'submit_responses') {
            handleSubmitResponsesRequest();
        } else {
            echo json_encode(['error' => 'Invalid action or action parameter missing for POST request']);
            http_response_code(400);
        }
        break;
    default:
        echo json_encode(['error' => 'Method not supported']);
        http_response_code(405);
}

function handleGetQuestionsRequest() {
    $pdo = getDatabaseConnection();
    $formId = isset($_GET['formId']) ? (int)$_GET['formId'] : null;
    if (!$formId) {
        echo json_encode(['error' => 'Form ID is required']);
        http_response_code(400);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE FormID = :formId");
        $stmt->execute(['formId' => $formId]);
        $questions = $stmt->fetchAll();
        $formattedQuestions = [];

        foreach ($questions as $question) {
            $formattedQuestion = [
                'name' => $question['QuestionName'],
                'type' => $question['QuestionType'],
                'required' => $question['QuestionRequired'] ? 'yes' : 'no',
                'text' => $question['QuestionText'],
                'description' => $question['QuestionDescription'] ?? '',
            ];

            $formattedQuestions[] = $formattedQuestion;
        }

        echo json_encode(['questions' => $formattedQuestions]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
}

function handleSubmitResponsesRequest() {
    $pdo = getDatabaseConnection();
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->beginTransaction();
        foreach ($data['responses'] as $response) {
            $stmt = $pdo->prepare("INSERT INTO responses (QuestionResponse, FormID, QuestionID, DateCreated, DateModified) VALUES (:response, :formId, :questionId, NOW(), NOW())");
            $stmt->execute([
                'response' => $response['answer'],
                'formId' => $response['formId'],
                'questionId' => $response['questionId']
            ]);
        }
        $pdo->commit();
        echo json_encode(['success' => 'Responses submitted successfully']);
    } catch (\PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit responses: ' . $e->getMessage()]);
    }
}

function handleFetchResponsesRequest() {
    $pdo = getDatabaseConnection();
    try {
        $stmt = $pdo->query("SELECT * FROM responses");
        $responses = $stmt->fetchAll();
        echo json_encode(['responses' => $responses]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch responses: ' . $e->getMessage()]);
    }
}

function handleDownloadCertificateRequest() {
    $pdo = getDatabaseConnection();
    $certificateId = isset($_GET['certificateId']) ? $_GET['certificateId'] : null;

    if (!$certificateId) {
        echo json_encode(['error' => 'Certificate ID is required']);
        http_response_code(400);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT FilePath FROM question_file_type WHERE FileTypeID = :certificateId");
        $stmt->execute(['certificateId' => $certificateId]);
        $file = $stmt->fetch();

        if ($file) {
            $filePath = $file['FilePath'];

            if (file_exists($filePath) && is_readable($filePath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));
                flush();
                readfile($filePath);
                exit;
            } else {
                echo json_encode(['error' => 'File does not exist or is not readable']);
                http_response_code(404);
            }
        } else {
            echo json_encode(['error' => 'No certificate found with the provided ID']);
            http_response_code(404);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to download certificate: ' . $e->getMessage()]);
    }
}

function handleGetAllResponsesAndQuestions() {
    $pdo = getDatabaseConnection();

    try {
        $stmtQuestions = $pdo->query("SELECT * FROM questions");
        $questions = $stmtQuestions->fetchAll();
        
        $stmtResponses = $pdo->query("SELECT * FROM responses");
        $responses = $stmtResponses->fetchAll();

        echo json_encode(['questions' => $questions, 'responses' => $responses]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch data: ' . $e->getMessage()]);
    }
}
?>