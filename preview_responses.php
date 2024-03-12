<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Preview Sky Survey Form Responses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><b>Preview Sky Survey Form Responses</b></a>
    </div>
</nav>

<div class="container mt-4">
    <h3>Preview Responses</h3>
    <form id="previewForm" method="POST" action="submit_to_database.php">
        <!-- Form responses will be populated here -->
        <button type="submit" id="submitFormButton" class="btn custom-btn">Submit Responses</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
