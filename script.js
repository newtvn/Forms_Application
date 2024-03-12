document.addEventListener('DOMContentLoaded', function() {
    const surveyForm = document.getElementById('surveyForm');

    fetchQuestions();

    surveyForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        submitForm(); // Submit the form data
    });
});

function fetchQuestions() {
    fetch(`http://localhost/forms_application/api.php?action=get_questions&formId=4`)
        .then(response => response.text()) // Assuming the response is now XML
        .then(str => (new window.DOMParser()).parseFromString(str, "text/xml"))
        .then(data => {
            const questions = data.getElementsByTagName('question');
            generateFormFields(questions);
        })
        .catch(error => console.error('Error fetching questions:', error));
}

function generateFormFields(questions) {
    const form = document.getElementById('surveyForm');
    Array.from(questions).forEach(question => {
        const formGroup = document.createElement('div');
        formGroup.className = 'mb-3';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = question.getElementsByTagName('text')[0].textContent;
        label.htmlFor = question.getAttribute('name');

        const input = document.createElement('input');
        input.type = question.getAttribute('type');
        input.className = 'form-control';
        input.name = question.getAttribute('name');
        input.id = question.getAttribute('name');
        input.required = question.getAttribute('required') === 'yes';

        formGroup.appendChild(label);
        formGroup.appendChild(input);
        form.insertBefore(formGroup, form.lastElementChild); 
    });
}

function fetchResponses() {
    fetch(`http://localhost/forms_application/api.php?action=get_all_responses&formId=4`)
        .then(handleResponse)
        .then(data => populateResponses(data.responses))
        .catch(handleError);
}

function populateResponses(responses) {
    const tableBody = document.getElementById('responseTableBody');
    // Code to populate table body
}

function submitResponses(formData) {
    fetch('http://localhost/forms_application/api.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'submit_responses', formId: 4, responses: Object.fromEntries(formData) }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(handleResponse)
    .then(data => alert('Survey response has been recorded.'))
    .catch(handleError);
}

function downloadCertificate(certificateId) {
    if (!certificateId) {
        alert("No certificate ID provided.");
        return;
    }
    
    fetch(`http://localhost/forms_application/api.php?action=download_certificate&certificateId=${certificateId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `certificate_${certificateId}.pdf`; 
            document.body.appendChild(a); 
            a.click();
            window.URL.revokeObjectURL(url); 
            document.body.removeChild(a); 
        })
        .catch(error => {
            console.error('Download error:', error);
            alert("Error downloading the certificate.");
        });
}

function handleResponse(response) {
    if (!response.ok) throw new Error('Network response was not ok');
    return response.json();
}

function handleError(error) {
    console.error('Fetch error:', error);
}



document.addEventListener('DOMContentLoaded', function() {
    const surveyForm = document.getElementById('surveyForm');

    fetchQuestions();

    surveyForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        submitForm(); // Submit the form data
    });
});

function submitForm() {
    const surveyForm = document.getElementById('surveyForm');
    const formData = new FormData(surveyForm);
    const formObject = Object.fromEntries(formData);

    fetch('http://localhost/forms_application/api.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'submit_responses', formId: 4, responses: formObject }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Your responses have been saved!');
        } else {
            alert('There was a problem saving your responses.');
        }
    })
    .catch(error => console.error('Error submitting form:', error));
}
