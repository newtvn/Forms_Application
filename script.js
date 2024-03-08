document.addEventListener('DOMContentLoaded', function() {
    const surveyForm = document.getElementById('surveyForm');
    const formId = 1;
   
    function fetchQuestions() {
        fetch(`http://localhost/forms_application/api.php?action=get_questions&formId=${formId}`)
        .then(response => response.json())
        .then(data => {
            if (data.questions) {
                generateFormFields(data.questions);
            } else {
                throw new Error('Failed to fetch questions.');
            }
        })
        .catch((error) => {
            console.error('Error fetching questions:', error);
        });
    }

    function generateFormFields(questions) {
        const formElement = document.getElementById('surveyForm');
        formElement.innerHTML = '';

        questions.forEach(question => {
            const label = document.createElement('label');
            label.innerHTML = question.questionText; // Assume questionText contains the question
            formElement.appendChild(label);

            const input = document.createElement('input');
            input.setAttribute('type', 'text');
            input.setAttribute('name', question.questionId); // Assume questionId is the unique identifier
            formElement.appendChild(input);

            formElement.appendChild(document.createElement('br'));
        });

        const submitButton = document.createElement('input');
        submitButton.setAttribute('type', 'submit');
        submitButton.value = 'Submit Survey';
        formElement.appendChild(submitButton);
    }

    function submitResponses(formData) {
        let responses = [];
        for (let [key, value] of formData.entries()) {
            responses.push({questionId: key, answer: value, formId: formId});
        }
        
        fetch('http://localhost/forms_application/api.php?action=submit_responses', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({responses: responses})
        })
        .then(response => response.json()) 
        .then(data => {
            if (data.success) {
                alert('Survey response has been recorded.');
                showDownloadCertificateOption();
            } else {
                throw new Error('An error occurred while submitting the survey.');
            }
        })
        .catch((error) => {
            console.error('Error submitting responses:', error);
        });
    }

    function showDownloadCertificateOption() {
        const downloadButton = document.createElement('button');
        downloadButton.innerHTML = 'Download Certificate';
        downloadButton.onclick = function() {
            downloadCertificate();
        };
        document.body.appendChild(downloadButton);
    }

    function downloadCertificate() {
        window.location = `http://localhost/forms_application/api.php?action=download_certificate&formId=${formId}`;
    }

    fetchQuestions();

    surveyForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(surveyForm);
        submitResponses(formData);
    });
});
