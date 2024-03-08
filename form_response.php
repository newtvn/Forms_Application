<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Responses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Link to the external CSS file -->
</head>
<body>
    <!-- Your HTML content here -->

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
