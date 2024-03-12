<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
        echo '<error>Database connection failed: ' . $e->getMessage() . '</error>';
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
                    echo '<error>Invalid action</error>';
                    http_response_code(400);
                    break;
            }
        } else {
            echo '<error>Action parameter is required</error>';
            http_response_code(400);
        }
        break;
    case 'POST':
        if (isset($_POST['action']) && $_POST['action'] == 'submit_responses') {
            handleSubmitResponsesRequest();
        } else {
            echo '<error>Invalid action or action parameter missing for POST request</error>';
            http_response_code(400);
        }
        break;
    default:
        echo '<error>Method not supported</error>';
        http_response_code(405);
}

function handleGetQuestionsRequest() {
    $pdo = getDatabaseConnection();
    $formId = isset($_GET['formId']) ? (int)$_GET['formId'] : null;
    if (!$formId) {
        echo '<error>Form ID is required</error>';
        http_response_code(400);
        return;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT q.*, qc.ChoiceValue, qc.ChoiceDescription, qcp.Multiple 
            FROM questions q
            LEFT JOIN question_choice qc ON q.QuestionID = qc.QuestionID
            LEFT JOIN question_choice_parameters qcp ON q.QuestionID = qcp.QuestionID
            WHERE q.FormID = :formId
            ORDER BY q.QuestionID, qc.ChoiceID
        ");
        $stmt->execute(['formId' => $formId]);
        $results = $stmt->fetchAll();
        $formattedQuestions = [];
        $choicesBuffer = [];
        foreach ($results as $row) {
            $qid = $row['QuestionID'];
            if (!isset($formattedQuestions[$qid])) {
                $formattedQuestions[$qid] = [
                    'name' => $row['QuestionName'],
                    'type' => $row['QuestionType'],
                    'required' => $row['QuestionRequired'] ? 'yes' : 'no',
                    'text' => $row['QuestionText'],
                    'description' => $row['QuestionDescription'] ?? '',
                    'choices' => [],
                    'multiple' => $row['Multiple'] === 'Yes' ? 'yes' : 'no'
                ];
            }
            if (!empty($row['ChoiceValue'])) {
                $choiceDescription = $row['ChoiceDescription'] ?? $row['ChoiceValue']; 
                $choicesBuffer[$qid][$row['ChoiceValue']] = $choiceDescription;
            }
        }
        foreach ($choicesBuffer as $qid => $choices) {
            $formattedQuestions[$qid]['choices'] = array_values($choices);
        }
        $formattedQuestions = array_values($formattedQuestions);
        $xmlData = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        arrayToXml($formattedQuestions, $xmlData);
        sendXmlHeader();
        echo $xmlData->asXML();
    } catch (\PDOException $e) {
        http_response_code(500);
        echo '<error>Query failed: ' . $e->getMessage() . '</error>';
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
        sendXmlHeader();
        echo '<success>Responses submitted successfully</success>';
    } catch (\PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo '<error>Failed to submit responses: ' . $e->getMessage() . '</error>';
    }
}

function handleFetchResponsesRequest() {
    $pdo = getDatabaseConnection();
    try {
        $stmt = $pdo->query("SELECT * FROM responses");
        $responses = $stmt->fetchAll();
        $xmlData = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        arrayToXml($responses, $xmlData);
        sendXmlHeader();
        echo $xmlData->asXML();
    } catch (\PDOException $e) {
        http_response_code(500);
        echo '<error>Failed to fetch responses: ' . $e->getMessage() . '</error>';
    }
}

function handleDownloadCertificateRequest() {
    $pdo = getDatabaseConnection();
    $certificateId = isset($_GET['certificateId']) ? $_GET['certificateId'] : null;
    if (!$certificateId) {
        echo '<error>Certificate ID is required</error>';
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
                echo '<error>File does not exist or is not readable</error>';
                http_response_code(404);
            }
        } else {
            echo '<error>No certificate found with the provided ID</error>';
            http_response_code(404);
        }
    } catch (\PDOException $e) {
        http_response_code(500);
        echo '<error>Failed to download certificate: ' . $e->getMessage() . '</error>';
    }
}

function handleGetAllResponsesAndQuestions() {
    $pdo = getDatabaseConnection();
    try {
        $stmtQuestions = $pdo->query("SELECT * FROM questions");
        $questions = $stmtQuestions->fetchAll();
        $stmtResponses = $pdo->query("SELECT * FROM responses");
        $responses = $stmtResponses->fetchAll();
        $xmlData = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        arrayToXml(['questions' => $questions, 'responses' => $responses], $xmlData);
        sendXmlHeader();
        echo $xmlData->asXML();
    } catch (\PDOException $e) {
        http_response_code(500);
        echo '<error>Failed to fetch data: ' . $e->getMessage() . '</error>';
    }
}

function arrayToXml($array, &$xml_user_info) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (!is_numeric($key)) {
                $subnode = $xml_user_info->addChild("$key");
                arrayToXml($value, $subnode);
            } else {
                $subnode = $xml_user_info->addChild("item");
                arrayToXml($value, $subnode);
            }
        } else {
            // Check if the key is a valid XML tag name
            if (preg_match('/^[a-z_]\w*$/i', $key)) {
                $xml_user_info->addChild("$key", htmlspecialchars("$value"));
            } else {
                // Replace invalid characters in the key
                $validKey = preg_replace('/[^a-z_]\w*/i', '_', $key);
                $xml_user_info->addChild("$validKey", htmlspecialchars("$value"));
            }
        }
    }
}


function sendXmlHeader() {
    header("Content-Type: application/xml; charset=utf-8");
}

?>