<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Responses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
    body {
      background: linear-gradient(135deg, #4d3319 0%, #1a1a00 100%); 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #fff;
    }
    #surveyResponses {
        max-width: 100%; 
        padding: 20px;
    }
    table {
        width: calc(100% - 40px);
        border-collapse: separate;
        border-spacing: 0;
        margin: 20px; 
        background-color: #fff; 
        color: #333;
        overflow: hidden; 
    }
    th, td {
        border-bottom: 1px solid #ddd; 
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #4F4F4F;
        color: white;
    }
    tr:last-child td {
        border-bottom: none; 
    }
    tr:hover {
        background-color:#5C4033;
        color: #FFFFFF;
    }
    @media (max-width: 600px) {
        table, th, td {
            display: block;
        }
        th, td {
            text-align: justify;
        }
        tr {
            margin-bottom: 10px; 
            border-radius: 10px; 
        }
        td {
            border: none;
            border-bottom: 1px solid #ddd;
            position: relative;
            padding-left: 50%; 
        }
        td:before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            width: 45%;
            padding-left: 15px;
            font-weight: bold;
            text-align: left; 
        }
    }
    </style>
</head>
<body>
    <div id="surveyResponses" class="container">
        <h1>Form Response Details</h1>
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Description</th>
                    <th>Gender</th>
                    <th>Programming Stack</th>
                    <th>Certificates</th>
                    <th>Date Responded</th>
                </tr>
            </thead>
            <tbody>
                <!-- Dynamically filled -->
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetch('http://localhost/forms_application/api.php?action=get_all_responses')
            .then(response => response.json())
            .then(data => {
                if (data.questions && data.responses) {
                    const tbody = document.querySelector('table tbody');
                    data.responses.forEach((response) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `<td>${response.full_name}</td><td>${response.email_address}</td><td>${response.description}</td><td>${response.gender}</td><td>${response.programming_stack}</td><td>${response.certificates}</td><td>${response.date_responded}</td>`;
                        tbody.appendChild(tr);
                    });
                } else {
                    throw new Error('Failed to fetch questions and responses.');
                }
            })
            .catch((error) => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
