<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Responses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fas fa-star"></i><b>Sky Survey Form</b></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            </ul>
            <form class="d-flex" role="search">
                <button class="btn btn-outline-success" type="button">
                    <a class="nav-link active" aria-current="page" href="form.php">Survey Form</a>
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2>Responses for Form ID: <?php echo $formId; ?></h2>
    <div class="table-responsive">
        <table class="table table-striped">
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
            <tbody id="responseTableBody">
                <!-- Response rows will be added here -->
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formId = 1; // The ID of the form you want to display responses for
        fetch(`http://localhost/forms_application/api.php?action=get_all_responses&formId=${formId}`)
            .then(response => response.json())
            .then(data => {
                if (data.responses) {
                    const tableBody = document.getElementById('responseTableBody');
                    data.responses.forEach(response => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${response.full_name}</td>
                            <td>${response.email_address}</td>
                            <td>${response.description}</td>
                            <td>${response.gender}</td>
                            <td>${response.programming_stack}</td>
                            <td>${response.certificates}</td>
                            <td>${response.date_responded}</td>
                        `;
                        tableBody.appendChild(tr);
                    });
                } else {
                    throw new Error('Failed to fetch responses.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
