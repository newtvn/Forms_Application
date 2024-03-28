<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

function getDatabaseConnection() {
    $host = '127.0.0.1';
    $db = 'sky_survey_database';
    $user = 'root';
    $pass = '8520';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo "<error>Database connection failed: " . $e->getMessage() . "</error>";
        exit;
    }
}

$pdo = getDatabaseConnection();

if (!$pdo) {
    sendXmlHeader();
    echo '<error>Database connection not established.</error>';
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'PUT':
        $inputXmlString = file_get_contents('php://input');
        
        if (trim($inputXmlString) === '') {
            sendXmlHeader();
            echo "<error>No XML input found. Ensure the request body is not empty and Content-Type is set to application/xml.</error>";
            http_response_code(400);
            exit;
        }
    
        $inputXml = simplexml_load_string($inputXmlString);
    
        if ($inputXml === false) {
            sendXmlHeader();
            $errorMessages = libxml_get_errors();
            $errors = array_map(function($error) {
                return trim($error->message);
            }, $errorMessages);
            echo "<error>Invalid XML format: " . htmlspecialchars(implode(", ", $errors)) . "</error>";
            http_response_code(400);
            libxml_clear_errors();
            exit;
        }
    
        $action = isset($inputXml->action) ? (string)$inputXml->action : null;
        
        if ($action) {
            switch ($action) {
                case 'submit_responses':
                    $formId = isset($inputXml->formId) ? (int)$inputXml->formId : null;
                    if (empty($formId)) {
                        sendXmlHeader();
                        echo '<error>Missing formId</error>';
                        http_response_code(400);
                        exit;}

                    handleSubmitResponsesRequest($pdo, $inputXml->responses, $formId);
                    break;
                
                default:
                    sendXmlHeader();
                    echo '<error>Invalid action for PUT request</error>';
                    http_response_code(400);
                    exit;
            }
        } else {
            sendXmlHeader();
            echo '<error>Action parameter is required for PUT request</error>';
            http_response_code(400);
            exit;
        }
        break;    
    case 'GET':
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action == 'get_questions') {
                handleGetQuestionsRequest($pdo);
            } elseif ($action == 'fetch_responses') {
                handleFetchResponsesRequest($pdo);
            } elseif ($action == 'download_certificate') {
                handleDownloadCertificateRequest($pdo);
            } elseif ($action == 'get_all_responses') {
                handleGetAllResponsesAndQuestions($pdo);
            } else {
                sendXmlHeader();
                echo '<error>Invalid action</error>';
                http_response_code(400);
            }
        } else {
            sendXmlHeader();
            echo '<error>Action parameter is required</error>';
            http_response_code(400);
        }
        break;
    case 'POST':
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            if ($action == 'submit_responses') {
                handleSubmitResponsesRequest($pdo);
            } else {
                sendXmlHeader();
                echo '<error>Invalid action for POST request</error>';
                http_response_code(400);
            }
        } else {
            sendXmlHeader();
            echo '<error>Action parameter missing for POST request</error>';
            http_response_code(400);
        }
        break;
    default:
        sendXmlHeader();
        echo '<error>HTTP Method not supported</error>';
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
            SELECT q.*, qcp.Multiple as ChoiceMultiple, qftp.Multiple as FileMultiple, qftp.MaxFileSize, qftp.MaxFileUnit
            FROM questions q
            LEFT JOIN question_choice_parameters qcp ON q.QuestionID = qcp.QuestionID
            LEFT JOIN question_file_type_parameters qftp ON q.QuestionID = qftp.QuestionID
            WHERE q.FormID = :formId
            ORDER BY q.QuestionID
        ");
        $stmt->execute(['formId' => $formId]);
        $results = $stmt->fetchAll();
        $xmlData = new SimpleXMLElement('<questions></questions>'); 

        foreach ($results as $row) {
            $question = $xmlData->addChild('question');
            $question->addAttribute('name', $row['QuestionName']);
            $question->addAttribute('type', $row['QuestionType']);
            $question->addAttribute('required', $row['QuestionRequired'] ? 'yes' : 'no');
            $question->addChild('text', $row['QuestionText']);
            if (!empty($row['QuestionDescription'])) {
                $question->addChild('description', $row['QuestionDescription']);
            }else {
                $question->addChild('description');
            }

            if ($row['QuestionType'] == 'choice') {
                $options = $question->addChild('options');
                $options->addAttribute('multiple', $row['ChoiceMultiple'] ? 'yes' : 'no');
                $choiceStmt = $pdo->prepare("
                    SELECT qc.choiceValue, qc.choiceDescription
                    FROM question_choice qc
                    WHERE qc.QuestionID = :questionId
                    ORDER BY qc.choiceID
                ");
                $choiceStmt->execute(['questionId' => $row['QuestionID']]);
                $choices = $choiceStmt->fetchAll();
                foreach ($choices as $choice) {
                    $option = $options->addChild('option', $choice['choiceDescription']);
                    $option->addAttribute('value', $choice['choiceValue']);
                }
            } elseif ($row['QuestionType'] == 'file') {
                $fileProps = $question->addChild('file_properties');
                $fileProps->addAttribute('format', '.pdf');
                $fileProps->addAttribute('max_file_size', $row['MaxFileSize']);
                $fileProps->addAttribute('max_file_size_unit', $row['MaxFileUnit']);
                $fileProps->addAttribute('multiple', $row['FileMultiple'] ? 'yes' : 'no');
            }
        }

        sendXmlHeader();
        echo $xmlData->asXML();
    } catch (\PDOException $e) {
        http_response_code(500);
        echo '<error>Query failed: ' . $e->getMessage() . '</error>';
    }
}

function generateUniqueParticipantID($pdo) {
    $unique = false;
    $participantId = 0;
    $maxAttempts = 10; 
    $attempt = 0;

    while (!$unique && $attempt < $maxAttempts) {
        $participantId = mt_rand(1000000, 9999999); 

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE ParticipantID = :participantId");
        $stmt->execute(['participantId' => $participantId]);
        $unique = ($stmt->fetchColumn() == 0);
        $attempt++;
    }

    if (!$unique) {
        throw new Exception("Failed to generate a unique ParticipantID after $maxAttempts attempts.");
    }

    return $participantId;
}


function handleSubmitResponsesRequest($pdo, $inputXml, $formId) {
    $participantId = generateUniqueParticipantID($pdo); 

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO participants (ParticipantID, FormID, DateCreated, DateModified) 
            VALUES (:participantId, :formId, NOW(), NOW())
        ");
        $stmt->execute([
            'participantId' => $participantId,
            'formId' => $formId
        ]);

        foreach ($inputXml->question_response as $responseXml) {
            $questionId = (int)$responseXml->QuestionID;
            $responseText = (string)$responseXml->Response;

            $stmt = $pdo->prepare("
                INSERT INTO responses (ParticipantID, FormID, QuestionID, QuestionResponse, DateCreated, DateModified) 
                VALUES (:participantId, :formId, :questionId, :questionResponse, NOW(), NOW())
            ");
            $stmt->execute([
                'participantId' => $participantId,
                'formId' => $formId,
                'questionId' => $questionId,
                'questionResponse' => $responseText
            ]);
        }

        $pdo->commit();

        sendXmlHeader();
        $xmlData = new SimpleXMLElement('<success></success>');
        $xmlData->addChild('message', 'Responses have been successfully submitted.');
        $xmlData->addChild('participantId', $participantId); 
        echo $xmlData->asXML();

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        sendXmlHeader();
        $xmlData = new SimpleXMLElement('<error></error>');
        $xmlData->addChild('message', 'Failed to submit responses: ' . $e->getMessage());
        echo $xmlData->asXML();
    }
}

function handleDownloadCertificateRequest() {
    $pdo = getDatabaseConnection();
    $certificateId = isset($_GET['certificateId']) ? $_GET['certificateId'] : null;

    if (!$certificateId) {
        sendErrorResponse('Certificate ID is required', 400);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT FilePath FROM question_file_type WHERE FileTypeID = :certificateId");
        $stmt->execute(['certificateId' => $certificateId]);
        $file = $stmt->fetch();

        if ($file) {
            $filePath = $file['FilePath'];
            sendFile($filePath);
        } else {
            sendErrorResponse('No certificate found with the provided ID', 404);
        }
    } catch (\PDOException $e) {
        sendErrorResponse('Failed to download certificate: ' . $e->getMessage(), 500);
    }
}


function sendErrorResponse($message, $code) {
    http_response_code($code);
    $xmlData = new SimpleXMLElement('<error></error>');
    $xmlData->addChild('message', $message);
    sendXmlHeader();
    echo $xmlData->asXML();
}


function sendFile($filePath) {
    if (file_exists($filePath) && is_readable($filePath)) {
    } else {
        sendErrorResponse('File does not exist or is not readable', 404);
    }
}


function sendXmlHeaderIfNotAlreadySent() {
    if (!headers_sent()) {
        header('Content-Type: application/xml; charset=utf-8');
    }
}

function handleGetAllResponsesAndQuestions($pdo, $currentPage = 1, $pageSize = 10, $emailFilter = null) {
    try {
        $baseQuery = "SELECT ResponsesID, QuestionResponse, DateCreated as DateResponded FROM responses";
        $whereClauses = [];
        $queryParams = [];
        if ($emailFilter) {
            $whereClauses[] = 'QuestionResponse LIKE :email';
            $queryParams[':email'] = '%' . $emailFilter . '%';
        }
        if (!empty($whereClauses)) {
            $baseQuery .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $totalQuery = "SELECT COUNT(*) FROM responses" . (!empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '');
        $totalStmt = $pdo->prepare($totalQuery);
        foreach ($queryParams as $key => &$val) {
            $totalStmt->bindParam($key, $val);
        }
        $totalStmt->execute();
        $totalResponses = $totalStmt->fetchColumn();
        $lastPage = ceil($totalResponses / $pageSize);

        $finalQuery = $baseQuery . " LIMIT :offset, :pageSize";
        $stmt = $pdo->prepare($finalQuery);
        foreach ($queryParams as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $offset = ($currentPage - 1) * $pageSize;
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $xmlData = new SimpleXMLElement('<question_responses/>');
        $xmlData->addAttribute('current_page', (string)$currentPage);
        $xmlData->addAttribute('last_page', (string)$lastPage);
        $xmlData->addAttribute('page_size', (string)$pageSize);
        $xmlData->addAttribute('total_count', (string)$totalResponses);

        foreach ($responses as $response) {
            $responseElement = $xmlData->addChild('question_response');
            $responseElement->addChild('response_id', $response['ResponsesID']);
            $responseValue = htmlspecialchars($response['QuestionResponse'], ENT_XML1, 'UTF-8');
            $responseElement->addChild('Response', $responseValue);
            $responseElement->addChild('date_responded', $response['DateResponded']);
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo $xmlData->asXML();

    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/xml; charset=utf-8');
        $xmlError = new SimpleXMLElement('<error/>');
        $xmlError->addChild('message', 'Failed to fetch responses: ' . $e->getMessage());
        echo $xmlError->asXML();
    }
}
   

function getAllResponsesByParticipant($pdo, $participantId, $currentPage = 1, $pageSize = 10) {
    try {
        $offset = ($currentPage - 1) * $pageSize;

       $baseQuery = "
            SELECT r.ResponsesID, r.QuestionResponse, r.DateCreated as DateResponded, /* other fields */
            FROM responses r
            WHERE r.ParticipantID = :participantId
        ";
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE ParticipantID = :participantId");
        $countStmt->execute(['participantId' => $participantId]);
        $totalCount = $countStmt->fetchColumn();
        $lastPage = ceil($totalCount / $pageSize);
        $stmt = $pdo->prepare("{$baseQuery} LIMIT :offset, :pageSize");
        $stmt->bindParam(':participantId', $participantId, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();

        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $xmlData = new SimpleXMLElement('<question_responses/>');
        $xmlData->addAttribute('current_page', (string)$currentPage);
        $xmlData->addAttribute('last_page', (string)$lastPage);
        $xmlData->addAttribute('page_size', (string)$pageSize);
        $xmlData->addAttribute('total_count', (string)$totalCount);

        foreach ($responses as $response) {
            $responseElement = $xmlData->addChild('question_response');
            $responseElement->addChild('response_id', $response['ResponsesID']);
            // Other child elements like full_name, email_address, etc. should be added here similarly.
            // ...
        $certificatesElement = $responseElement->addChild('certificates');
        $certStmt = $pdo->prepare("SELECT * FROM certificates WHERE ResponsesID = :responseId");
        $certStmt->execute(['responseId' => $response['ResponsesID']]);
        $certificates = $certStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($certificates as $certificate) {
            $certificateElement = $certificatesElement->addChild('certificate', $certificate['FileName']);
            $certificateElement->addAttribute('id', $certificate['CertificateID']);
        }
        $responseElement->addChild('date_responded', $response['DateResponded']);
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo $xmlData->asXML();

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/xml; charset=utf-8');
    $xmlError = new SimpleXMLElement('<error/>');
    $xmlError->addChild('message', 'Failed to fetch responses: ' . $e->getMessage());
    echo $xmlError->asXML();
}



function arrayToXml($array, &$xml) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (!is_numeric($key)) {
                $subnode = $xml->addChild("$key");
                arrayToXml($value, $subnode);
            } else {
                $subnode = $xml->addChild("item$key");
                arrayToXml($value, $subnode);
            }
        } else {
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

function sendXmlHeader() {
    header('Content-Type: application/xml');
}
?>




