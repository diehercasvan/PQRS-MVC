/*Author: DIEGO CASALLAS
Date: 2026-10-02
Description: This script handles form validation and submission using the AbortController API. It validates input fields, sends data asynchronously, and provides user feedback through messages.

*/
window.AbortController = window.AbortController || require('abort-controller').AbortController;
console.log('AbortController is available:', !!window.AbortController);
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM fully loaded and parsed');

  const controller = new AbortController();
  console.log('AbortController initialized:', controller);

  /**Variables*/

  const validationMessage = document.getElementById('validation-message');
  const preloader = document.getElementById('preloader');
  const toggleButton = document.getElementById('toggle-button');
  showPreloader(false); // Hide preloader initially

  document.addEventListener('submit', function (event) {
    event.preventDefault();
    console.log('Form submission intercepted:', event.target);
    showPreloader(true); // Show preloader when form is submitted
    // Simulate an asynchronous operation that can be aborted
    const objForm = document.querySelector('form');

    if (formValidation(objForm)) {
      console.log('Form validation passed, proceeding with submission...');
      const btnSubmit = objForm.querySelector('button[type="submit"]');
      btnSubmit.disabled = true; // Disable the submit button to prevent multiple submissions

      sendData(objForm, controller.signal)
        .then(response => {
          console.log('Data sent successfully:', response);
          messageValidation('Form submitted successfully!', 'success', 3000, 'fade');
          btnSubmit.disabled = false; // Re-enable the submit button after submission
        })
        .catch(error => {
          if (error.name === 'AbortError') {
            console.log('Request was aborted');
            messageValidation('Request was aborted.', 'error', 3000, 'fade');
            btnSubmit.disabled = false; // Re-enable the submit button after cancellation
          } else {
            console.error('Error sending data:', error);
            messageValidation('Error sending data.', 'error', 3000, 'fade');
            btnSubmit.disabled = false; // Re-enable the submit button after error
          }
        }).finally(() => {
          showPreloader(false); // Hide preloader after submission attempt
        });
    } else {
      console.log('Form validation failed, aborting submission.');
      controller.abort(); // Abort the request if validation fails
      showPreloader(false); // Hide preloader after submission attempt
    }

  }
  );

  function formValidation(objForm) {
    let validate = true;
    const inputs = objForm.querySelectorAll('input, textarea, select');
    try {
      for (const input of inputs) {
        if (!input.checkValidity()) {
          console.log(`Validation failed for input: ${input.name}`);
          messageValidation(input.validationMessage, 'error', 3000, 'fade');
          validate = false;
        }
        if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
          console.log(`Invalid email format for input: ${input.name}`);
          messageValidation('Invalid email format.', 'error', 3000, 'fade');
          validate = false;
        }
        if (input.type === 'tel' && !/^\+?[0-9\s\-()]+$/.test(input.value)) {
          console.log(`Invalid phone number format for input: ${input.name}`);
          messageValidation('Invalid phone number format.', 'error', 3000, 'fade');
          validate = false;
        }
        if (input.type === 'password' && input.value.length < 8) {
          console.log(`Password too short for input: ${input.name}`);
          messageValidation('Password must be at least 8 characters long.', 'error', 3000, 'fade');
          validate = false;
        }
      }

      return validate;
    }
    catch (error) {
      console.error('Error during form validation:', error);
      messageValidation('An error occurred during validation.', 'error', 3000, 'fade');
      return false;

    }
  }
  async function sendData(form, signal) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    console.log('Sending data:', data);

    signal.addEventListener('abort', () => {
      console.log('Abort signal received, cancelling request...');
    });

    return null;


  }
  function messageValidation(message, type, time, animation) {
    validationMessage.textContent = message;
    validationMessage.style.display = 'block';
    validationMessage.className = `validation-message ${type} ${animation}`;

    setTimeout(() => {
      validationMessage.style.display = 'none';
    }, time);
  }

  function showPreloader(visible) {

    setTimeout(() => {
      preloader.style.display = visible ? 'block' : 'none';
      preloader.className = visible ? 'preloader show' : 'preloader hide';
      preloader.textContent = visible ? 'Cargando...' : '';
      preloader.style.opacity = visible ? '1' : '0';
    }, 1000); // Delay of 1 second before hiding the preloader

  }

  /* Toggle button functionality */
  toggleButton.addEventListener('click', function () {
    const nav = document.querySelector('nav');
    if (nav.style.display === 'block') {
      nav.style.display = 'none';
    } else {
      nav.style.display = 'block';
    }
  });

});


