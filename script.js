document.addEventListener('DOMContentLoaded', function() {
    const surveyForm = document.getElementById('surveyForm');
    const formId = 4;

    function fetchQuestions() {
        fetch(`http://localhost/forms_application/api.php?action=get_questions&formId=4`)
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
        questions.forEach(question => {
            const formGroup = document.createElement('div');
            formGroup.classList.add('mb-3');
            const label = document.createElement('label');
            label.textContent = question.text;
            label.setAttribute('for', `question_${question.name}`);
            let input;

            if (question.type === 'long_text') {
                input = document.createElement('textarea');
            } else if (question.type === 'choice') {
                input = document.createElement('select');
                const choices = getChoicesForQuestion(question.name);
                choices.forEach(choiceValue => {
                    const option = document.createElement('option');
                    option.value = choiceValue;
                    option.textContent = choiceValue;
                    input.appendChild(option);
                });
            } else {
                input = document.createElement('input');
                input.type = question.type === 'email' ? 'email' : question.type === 'file' ? 'file' : 'text';
            }
            input.classList.add('form-control');
            input.setAttribute('id', `question_${question.name}`);
            input.setAttribute('name', question.name);
            formGroup.appendChild(label);
            formGroup.appendChild(input);
            surveyForm.appendChild(formGroup);
        });

        const previewButton = document.createElement('button');
        previewButton.type = 'button';
        previewButton.textContent = 'Preview Answers';
        previewButton.addEventListener('click', previewAnswers);
        surveyForm.appendChild(previewButton);

        const submitButton = document.createElement('button');
        submitButton.type = 'button';
        submitButton.textContent = 'Submit Survey';
        submitButton.addEventListener('click', () => {
            const formData = new FormData(surveyForm);
            submitResponses(formData);
        });
        surveyForm.appendChild(submitButton);
    }

    function getChoicesForQuestion(questionId) {
        // Placeholder function for getting the choices for a given question ID
        // Replace this with the actual logic to retrieve choices based on the question ID
        const choiceMap = {
            '2': ['REACT', 'ANGULAR', 'VUE', 'SQL', 'POSTGRES', 'MYSQL', 'MSSQL', 'Java', 'PHP', 'GO', 'RUST'],
            '4': ['Yes', 'No'],
            '5': ['MALE', 'FEMALE', 'OTHER']
        };
        return choiceMap[questionId] || [];
    }

    function previewAnswers() {
        const formData = new FormData(surveyForm);
        const previewArea = document.getElementById('previewArea');
        previewArea.innerHTML = '';
        formData.forEach((value, key) => {
            const responseParagraph = document.createElement('p');
            responseParagraph.textContent = `${key}: ${value}`;
            previewArea.appendChild(responseParagraph);
        });
        previewArea.style.display = 'block';
    }

    function submitResponses(formData) {
        let responses = [];
        formData.forEach((value, key) => {
            responses.push({ questionName: key, answer: value });
        });
        fetch('http://localhost/forms_application/api.php?action=submit_responses', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ responses: responses })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Survey response has been recorded.');
            } else {
                throw new Error('An error occurred while submitting the survey.');
            }
        })
        .catch((error) => {
            console.error('Error submitting responses:', error);
        });
    }

    function fetchResponses() {
        fetch(`http://localhost/forms_application/api.php?action=get_all_responses`)
            .then(response => response.json())
            .then(data => {
                if (data && Array.isArray(data)) {
                    const filteredResponses = data.filter(response => response.FormID == formId);
                    if (responsesContainer) {
                        responsesContainer.innerHTML = ''; // Clear existing responses
                        filteredResponses.forEach((response) => {
                            const div = document.createElement('div');
                            div.innerHTML = `<p>Question: ${response.QuestionText}</p><p>Answer: ${response.QuestionResponse}</p>`;
                            responsesContainer.appendChild(div);
                        });
                    }
                } else {
                    throw new Error('Failed to fetch responses.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }



    fetchQuestions();
    fetchResponses();
    
    surveyForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        submitResponses(formData);
    });
});




    

  
