document.addEventListener('DOMContentLoaded', function() {
    const surveyForm = document.getElementById('surveyForm');
    fetchQuestions();
    surveyForm.addEventListener('submit', function(event) {
        event.preventDefault();
        submitForm();
    });
});

function fetchQuestions() {
    fetch(`http://localhost/forms_application/api.php?action=get_questions&formId=6`)
        .then(response => response.text())
        .then(str => (new window.DOMParser()).parseFromString(str, "text/xml"))
        .then(data => {
            const questions = data.querySelectorAll('question');
            generateFormFields(questions);
        })
        .catch(error => console.error('Error fetching questions:', error));
}

function submitForm() {
    const surveyForm = document.getElementById('surveyForm');
    var xmlDataString = `
        <request>
            <action>submit_responses</action>
            <formId>${surveyForm.elements['formId'].value}</formId>
            <responses>
                <response>
                    <full_name>${surveyForm.elements['full_name'].value}</full_name>
                    <email_address>${surveyForm.elements['email_address'].value}</email_address>
                    <description>${surveyForm.elements['description'].value}</description>
                    <gender>${surveyForm.elements['gender'].value}</gender>
                    <programming_stack>${surveyForm.elements['programming_stack'].value}</programming_stack>
                    <!-- Add other form fields as needed -->
                </response>
            </responses>
        </request>
    `;

    fetch('http://localhost/forms_application/api.php?action=submit_responses&formId=6', { 
        method: 'PUT',
        headers: {
            'Content-Type': 'application/xml; charset=utf-8'
        },
        body: xmlDataString
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text(); // or response.xml() if it returns XML
    })
    .then(data => {
        console.log(data);
        alert('Your responses have been saved!');
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        alert('There was a problem saving your responses.');
    });
}

function generateFormFields(questions) {
    const form = document.getElementById('surveyForm');
    
    questions.forEach(question => {
        const type = question.getAttribute('type');
        const name = question.getAttribute('name');
        const label = question.querySelector('text').textContent;
        const required = question.getAttribute('required') === 'yes';
        const formGroup = document.createElement('div');
        formGroup.className = 'mb-3';
        
        const labelElement = document.createElement('label');
        labelElement.className = 'form-label';
        labelElement.textContent = label;
        labelElement.htmlFor = name;
        formGroup.appendChild(labelElement);
        
        let input;
        if (type === 'choice') {
            const multiple = question.getAttribute('multiple') === 'yes';
            if (multiple) {
                input = document.createElement('div');
                const options = question.querySelectorAll('option');
                options.forEach(option => {
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = name;
                    checkbox.value = option.getAttribute('value');
                    if (required) checkbox.required = true;
                    input.appendChild(checkbox);

                    const optionLabel = document.createElement('label');
                    optionLabel.textContent = option.textContent;
                    input.appendChild(optionLabel);
                });
            } else {
                // Handle as radio buttons
                input = document.createElement('div');
                const options = question.querySelectorAll('option');
                options.forEach(option => {
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = name;
                    radio.value = option.getAttribute('value');
                    if (required) radio.required = true;
                    input.appendChild(radio);

                    const optionLabel = document.createElement('label');
                    optionLabel.textContent = option.textContent;
                    input.appendChild(optionLabel);
                });
            }
        } else if (type === 'file') {
            input = document.createElement('input');
            input.type = 'file';
            if (question.getAttribute('multiple') === 'yes') {
                input.setAttribute('multiple', '');
            }
        } else {
            input = document.createElement('input');
            input.type = 'text';
        }
        
        input.className = 'form-control';
        input.name = name;
        input.id = name;
        if (required) input.required = true;
        
        formGroup.appendChild(input);
        form.insertBefore(formGroup, form.lastElementChild);
    });
}


function fetchResponses() {
    fetch(`http://localhost/forms_application/api.php?action=get_all_responses`)
        .then(handleResponse)
        .then(data => populateResponses(data.responses))
        .catch(handleError);
}

function populateResponses(responses) {
    const tableBody = document.getElementById('responseTableBody');
    tableBody.innerHTML = '';
    responses.forEach(response => {
        const row = tableBody.insertRow();
        Object.values(response).forEach(text => {
            const cell = row.insertCell();
            cell.textContent = text;
        });
    });
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