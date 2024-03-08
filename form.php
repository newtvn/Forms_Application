<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sky_Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #4d3319 0%, #1a1a00 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
        }
        .container {
            max-width: 100%;
            padding: 20px;
            background-color: #fff;
            color: #333;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }
        h1 {
            color: #5c3c00;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            margin-bottom: .5rem;
            color: #5c3c00;
        }
        input[type="text"], input[type="email"], textarea, select {
            width: 100%;
            padding: .375rem .75rem;
            margin-bottom: 1rem;
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }
        button {
            padding: .375rem .75rem;
            background-color: #5c3c00;
            color: white;
            border: none;
            border-radius: .25rem;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #1D0200;
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>

<div class="container">

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('surveyForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch('http://localhost/forms_application/api.php?action=submit_responses', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Your response has been recorded.');
                // Redirect or handle post-submit logic here
            } else {
                throw new Error('An error occurred while submitting the form.');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
</script>

</body>
</html>
