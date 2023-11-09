/*
 * Copyright (c) 2023 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

document.addEventListener('DOMContentLoaded', function () {

    const container = document.querySelector('#view_order_payments_block'); // Replace with the actual container ID

    if (container && typeof refundData !== 'undefined') {
        // Create a div element with a specific class
        const divElement = document.createElement('div');
        divElement.className = 'card mt-2'; 
        const divSubelement = document.createElement('div');
        divSubelement.className = 'card-header'; 
        const titleElement = document.createElement('h3');
        titleElement.className = 'card-header-title';
        const refundDataArray = JSON.parse(refundData);
        titleElement.textContent = `Refund Details (${refundDataArray.length})`;
        
        divSubelement.appendChild(titleElement);
        const divbodyelement = document.createElement('div');
        divbodyelement.className = 'card-body'; 

        // Create a table element to display the refund details
        const tableElement = document.createElement('table');
        tableElement.className = 'table'; 

        // Create table headers
        const tableHeaders = ['Refund ID', 'Refund Description', 'Amount', 'Date','Comment'];
        const headerRow = document.createElement('tr');
        tableHeaders.forEach(headerText => {
            const headerCell = document.createElement('th');
            const headerDiv = document.createElement('div');
            headerDiv.textContent = headerText;
            headerCell.appendChild(headerDiv);
            headerRow.appendChild(headerCell);
        });

        // Append the header row to the table
        const thead = document.createElement('thead');
        thead.appendChild(headerRow);
        tableElement.appendChild(thead);

        var dollarSign = currency.sign;
        // Loop through the refund data and create table rows and cells for each detail
        refundDataArray.sort((a, b) => new Date(b.date_created) - new Date(a.date_created));
        refundDataArray.forEach(function (data) {
            const row = tableElement.insertRow();
            const idCell = row.insertCell();
            idCell.textContent = data.refund_id;
            const descriptionCell = row.insertCell();
            descriptionCell.textContent = data.refund_description;
            const amountCell = row.insertCell();
            amountCell.textContent = dollarSign + data.amount;
            const dateCell = row.insertCell();
            dateCell.textContent = data.date_created;
            const commentCell = row.insertCell();
            commentCell.textContent = data.comment;
        });

        divbodyelement.appendChild(tableElement);
        divElement.appendChild(divSubelement);
        divElement.appendChild(divbodyelement);

        container.insertAdjacentElement('beforebegin', divElement);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    
    const container = document.querySelector('#view_order_payments_block'); 

    if (container && typeof captureData !== 'undefined') {
        // Create a div element with a specific class
        const divElement = document.createElement('div');
        divElement.className = 'card mt-2'; 
        const divSubelement = document.createElement('div');
        divSubelement.className = 'card-header'; 
        const titleElement = document.createElement('h3');
        titleElement.className = 'card-header-title';
        const captureDataArray = JSON.parse(captureData);
        titleElement.textContent = `Capture Details (${captureDataArray.length})`;
        divSubelement.appendChild(titleElement);
        const divbodyelement = document.createElement('div');
        divbodyelement.className = 'card-body'; 
        // Create a table element to display the capture details
        const tableElement = document.createElement('table');
        tableElement.className = 'table'; // Replace with your desired class name

        var dollarSign = currency.sign;
        // Create table headers
        const tableHeaders = ['Capture ID', 'Amount','Date','Comment'];
        const headerRow = document.createElement('tr');
        tableHeaders.forEach(headerText => {
            const headerCell = document.createElement('th');
            const headerDiv = document.createElement('div');
            headerDiv.textContent = headerText;
            headerCell.appendChild(headerDiv);
            headerRow.appendChild(headerCell);
        });

        // Append the header row to the table
        const thead = document.createElement('thead');
        thead.appendChild(headerRow);
        tableElement.appendChild(thead);
        captureDataArray.sort((a, b) => new Date(b.date_created) - new Date(a.date_created));
        // Loop through the capture data and create table rows and cells for each detail
        captureDataArray.forEach(function (data) {
            const row = tableElement.insertRow();
            const idCell = row.insertCell();
            idCell.textContent = data.payment_transcation_id;
            const amountCell = row.insertCell();
            amountCell.textContent = dollarSign + data.amount;
            const dateCell = row.insertCell();
            dateCell.textContent = data.transcation_date;
            const commentCell = row.insertCell();
            commentCell.textContent = data.comment;
        });

        divbodyelement.appendChild(tableElement);
        divElement.appendChild(divSubelement);
        divElement.appendChild(divbodyelement);
        container.insertAdjacentElement('beforebegin', divElement);
    }
});


document.addEventListener('DOMContentLoaded', function() {
    var cancelButton = document.querySelector('#refundpartial');
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            var orderId = document.querySelector('form[name="cancel_product"]').getAttribute('data-order-id');
            var orderTotalElement = document.getElementById('orderTotal');
            // Check if the element exists
            if (orderTotalElement) {
                // Get the text content of the element
                var orderTotalValue = orderTotalElement.textContent;
                // Now, you have the value as a string, e.g., "$36.00"
                // If you want to work with it as a number, you can parse it
                var orderTotalNumber = parseFloat(orderTotalValue.replace('$', ''));
            }
            if(typeof refundmaxamount !== 'undefined'){ 
                refundamount = refundmaxamount;
                balanceamount = (orderTotalNumber - refundamount).toFixed(2);
            } else {
                balanceamount = orderTotalNumber.toFixed(2);
            }     
            const container = document.querySelector('.product-row'); 
            const fullRefundElement = document.querySelector('.full-refund-card');
            var partialRefundElement = document.querySelector('.partial-refund-card');
            
            if (container !== null) {
                // Remove the full refund option if it is already displayed
                if (fullRefundElement) {
                    fullRefundElement.remove();
                }
                if (partialRefundElement) {
                    partialRefundElement.style.display = partialRefundElement.style.display === 'none' ? 'block' : 'none';
                } else {
                    var dollarSign = currency.sign;
                    const divElement = document.createElement('div');
                    divElement.className = 'card partial-refund-card'; 
                    const titleElement = document.createElement('p');
                    titleElement.className = 'card-header';
                    titleElement.textContent = 'Partial Refund';
                    const amountInput = document.createElement('input');
                    amountInput.type = 'number';
                    amountInput.placeholder = 'Amount';
                    amountInput.className = 'form-control amount-input';
                    const maxAmountText = document.createElement('p');
                    maxAmountText.className = 'card-body balance-text';
                    maxAmountText.textContent = 'Max Amount: ' + dollarSign + balanceamount;           
                    const innerdiv = document.createElement('div');
                    innerdiv.className = 'reason-input-wrapper';
                    var reasonInput = document.createElement('textarea');
                    reasonInput.placeholder = 'Reason';
                    reasonInput.className = 'form-control reason-input';
                    reasonInput.rows = '4';
                    reasonInput.cols = '50';
                    reasonInput.maxLength = 100;
                    reasonInput.addEventListener('input', function() {
                        if (this.value.length >= 100) {
                            alert('You have Reached the Maximum Word Length of 100 Characters.');
                        } else {
                            this.setCustomValidity('');
                        }
                    });
                    const maxText = document.createElement('span');
                    maxText.className = 'card-body balance-text remaining-word-count';

                    innerdiv.appendChild(reasonInput);
                    innerdiv.appendChild(maxText);
                    document.body.appendChild(innerdiv);

                    // Get the remaining word length display element.
                    const remainingWordLengthDisplay = document.querySelector('.remaining-word-count');
                    // Update the remaining word length display whenever the user types in the textarea.
                    reasonInput.addEventListener('input', function() {
                        // Get the remaining word length.
                        const remainingWordLength = countRemainingWordLength(this);
                        // Display the remaining word length.
                        remainingWordLengthDisplay.innerHTML = `${remainingWordLength} characters remaining`;
                    });
                    // Display the initial remaining word length.
                    const initialRemainingWordLength = countRemainingWordLength(reasonInput);
                    remainingWordLengthDisplay.innerHTML = `${initialRemainingWordLength} characters remaining`;
                    // Create a button to submit the form
                    const submitButton = document.createElement('button');
                    submitButton.textContent = 'Submit';
                    submitButton.className = 'btn btn-primary submit';
                    submitButton.addEventListener('click', function() {
                        showLoader();
                        // Get the input values
                        const amount = amountInput.value;
                        const reason = reasonInput.value;
                        // Define the maximum allowed amount
                        const maxAmount = balanceamount; // Replace with your desired maximum amount
                        // Validate the input value
                        if (isNaN(parseFloat(amount)) || parseFloat(amount) <= 0 || parseFloat(amount) > parseFloat(maxAmount)) {
                            alert('Please enter a valid amount between 0 and ' + maxAmount);
                            hideLoader(); 
                            return;
                        }
                    
                        // Make an AJAX request to your PHP function
                        $.ajax({
                            type: 'POST',
                            cache: false,
                            dataType: 'json',
                            url: adminajax_link, 
                            data: {
                                ajax: true,
                                action: 'partialRefund',
                                RefundAmount: amount,
                                Refundreason: reason,
                                OrderId: orderId,
                                ProductAmount: orderTotalNumber,
                            },
                            success : function (data,response) {  
                                var response = JSON.parse(data); 
                                if (response.status === 'success') {   
                                    // Display the success message dialog                    
                                    $('#ajax_confirmation').html('A Partial Refund was Successfully Created.').show();
                                    // Clear the input fields    
                                    amountInput.value = '';
                                    reasonInput.value = '';   
                                    divElement.style.display = 'none';
                                    hideLoader(); 
                                    setTimeout(function(){
                                        window.location.href = window.location.href;
                                    }, 2000);    
                                } else if (response.status === 'failed') {
                                    // Display the failed message dialog
                                    $('#ajax_confirmation').html('The Partial Refund Failed.').show();
                                    $('#ajax_confirmation').css({'color': '#363a41','background-color': '#fbc6c3','border': '1px solid #f44336'});
                                    $('#ajax_confirmation').removeClass('alert-success').addClass('alert-danger');
                                    amountInput.value = '';
                                    reasonInput.value = '';   
                                    divElement.style.display = 'none';
                                    hideLoader(); 
                                    setTimeout(function(){
                                         window.location.href = window.location.href;
                                    }, 2000); 
                                }
                            },
                            error : function (data){
                                //alert(data);
                                $('#ajax_confirmation').html('Error : Partial Refund Failed.').show();
                                $('#ajax_confirmation').css({'color': '#363a41','background-color': '#fbc6c3','border': '1px solid #f44336'});
                                $('#ajax_confirmation').removeClass('alert-success').addClass('alert-danger');
                                amountInput.value = '';
                                reasonInput.value = '';   
                                divElement.style.display = 'none';
                                hideLoader();    
                            }
                        });
                    });

                    // Create a "Cancel" button
                    const cancelButton = document.createElement('button');
                    cancelButton.textContent = 'Cancel';
                    cancelButton.className = 'btn btn-secondary';
                    cancelButton.addEventListener('click', function() {
                        // Handle the cancel action (e.g., close the modal)
                        // You can add your code to close the modal here
                        amountInput.value = '';
                        reasonInput.value = '';
                        divElement.style.display = 'none';
                    });
                    divElement.appendChild(titleElement);
                    divElement.appendChild(amountInput);
                    divElement.appendChild(maxAmountText);
                    divElement.appendChild(innerdiv);
                    divElement.appendChild(submitButton);
                    divElement.appendChild(cancelButton);

                    container.insertAdjacentElement('beforebegin', divElement);
                }
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var refundButton = document.querySelector('#fullrefund');
    if (refundButton) {
        refundButton.addEventListener('click', function() {
            var orderId = document.querySelector('form[name="cancel_product"]').getAttribute('data-order-id');
            var orderTotalElement = document.getElementById('orderTotal');
            // Check if the element exists
            if (orderTotalElement) {
                // Get the text content of the element
                var orderTotalValue = orderTotalElement.textContent;
                // Now, you have the value as a string, e.g., "$36.00"
                // If you want to work with it as a number, you can parse it
                var orderTotalNumber = parseFloat(orderTotalValue.replace('$', ''));
            }
            const container = document.querySelector('.product-row'); // Replace with the actual container ID
            const partialRefundElement = document.querySelector('.partial-refund-card');
            var fullRefundElement = document.querySelector('.full-refund-card');
            var dollarSign = currency.sign;
            if (container !== null) {
                // Remove the partial refund option if it is already displayed
                if (partialRefundElement) {
                    partialRefundElement.remove();
                }
                if (fullRefundElement) {
                fullRefundElement.style.display = fullRefundElement.style.display === 'none' ? 'block' : 'none';
                } else {
                    // Create a div element with a specific class
                    const divElement = document.createElement('div');
                    divElement.className = 'card full-refund-card'; // Replace with your desired class name
                    // Create a <p> element for the title "Partial Refund"
                    const titleElement = document.createElement('p');
                    titleElement.className = 'card-header';
                    titleElement.textContent = 'Full Refund';
                    // Create an input field for "Amount"
                    const amountElement= document.createElement('p');
                    amountElement.className = 'card-body amount-display';
                    amountElement.textContent ='Refund Amount: ' + dollarSign + orderTotalNumber.toFixed(2);
                    const innerdiv = document.createElement('div');
                    innerdiv.className = 'reason-input-wrapper';

                    var reasonInput = document.createElement('textarea');
                    reasonInput.placeholder = 'Reason';
                    reasonInput.className = 'form-control reason-input';
                    reasonInput.rows = '4';
                    reasonInput.cols = '50';
                    reasonInput.maxLength = 100;
                    reasonInput.addEventListener('input', function() {
                        if (this.value.length >= 100) {
                            alert('You have reached the maximum word length of 100 characters.');
                        } else {
                            this.setCustomValidity('');
                        }
                    });
                    const maxText = document.createElement('span');
                    maxText.className = 'card-body balance-text remaining-word-count';
                    innerdiv.appendChild(reasonInput);
                    innerdiv.appendChild(maxText);
                    document.body.appendChild(innerdiv);
                    // Get the remaining word length display element.
                    const remainingWordLengthDisplay = document.querySelector('.remaining-word-count');
                    // Update the remaining word length display whenever the user types in the textarea.
                    reasonInput.addEventListener('input', function() {
                        // Get the remaining word length.
                        const remainingWordLength = countRemainingWordLength(this);
                        // Display the remaining word length.
                        remainingWordLengthDisplay.innerHTML = `${remainingWordLength} characters remaining`;
                    });
                    // Display the initial remaining word length.
                    const initialRemainingWordLength = countRemainingWordLength(reasonInput);
                    remainingWordLengthDisplay.innerHTML = `${initialRemainingWordLength} characters remaining`;
                    // Create a button to submit the form
                    const submitButton = document.createElement('button');
                    submitButton.textContent = 'Submit';
                    submitButton.className = 'btn btn-primary submit';
                    submitButton.addEventListener('click', function() {
                        showLoader();
                        // Get the input values
                        const amount = orderTotalNumber;
                        const reason = reasonInput.value;            
                        // Make an AJAX request to your PHP function
                        $.ajax({
                            type: 'POST',
                            cache: false,
                            dataType: 'json',
                            url: adminajax_link, 
                            data: {
                                ajax: true,
                                action: 'fullRefund',
                                RefundAmount: amount,
                                Refundreason: reason,
                                OrderId: orderId,
                            },
                            success : function (data,response) {  
                                var response = JSON.parse(data); 
                                if (response.status === 'success') {   
                                    // Display the success message dialog                    
                                    $('#ajax_confirmation').html('A Full Refund was Successfully Created.').show();
                                    // Clear the input fields
                                    reasonInput.value = '';   
                                    divElement.style.display = 'none';
                                    hideLoader(); 
                                    setTimeout(function(){
                                         window.location.href = window.location.href;
                                    }, 2000);    
                                } else if (response.status === 'failed') {
                                    // Display the failed message dialog
                                    $('#ajax_confirmation').html('The Full Refund Failed.').show();
                                    $('#ajax_confirmation').css({'color': '#363a41','background-color': '#fbc6c3','border': '1px solid #f44336'});
                                    $('#ajax_confirmation').removeClass('alert-success').addClass('alert-danger');
                                    reasonInput.value = '';   
                                    divElement.style.display = 'none';
                                    hideLoader(); 
                                    setTimeout(function(){
                                        window.location.href = window.location.href;
                                    }, 2000); 
                                }
                            },    
                            error : function (data){
                                $('#ajax_confirmation').html('Error : Full Refund Failed.').show();
                                $('#ajax_confirmation').css({'color': '#363a41','background-color': '#fbc6c3','border': '1px solid #f44336'});
                                $('#ajax_confirmation').removeClass('alert-success').addClass('alert-danger');
                                reasonInput.value = '';   
                                divElement.style.display = 'none';
                                hideLoader();
                            }        
                        });
                    });

                    // Create a "Cancel" button
                    const cancelButton = document.createElement('button');
                    cancelButton.textContent = 'Cancel';
                    cancelButton.className = 'btn btn-secondary';
                    cancelButton.addEventListener('click', function() {
                        // Handle the cancel action (e.g., close the modal)
                        // You can add your code to close the modal here
                        reasonInput.value = ''; 
                        divElement.style.display = 'none';
                    });
                    divElement.appendChild(titleElement);
                    divElement.appendChild(amountElement);
                    divElement.appendChild(innerdiv);
                    divElement.appendChild(submitButton);
                    divElement.appendChild(cancelButton);
                    container.insertAdjacentElement('beforebegin', divElement);
                }
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var partialRefundButton = document.querySelector('.partial-refund-display');
    if (partialRefundButton) {
        partialRefundButton.style.display = 'none';
    }
});

function showLoader() {
    // Create and display the loader
    const loader = document.createElement('div');
    loader.className = 'loader';
    document.body.appendChild(loader);
}

function hideLoader() {
    // Hide the loader
    const loader = document.querySelector('.loader');
    if (loader) {
        document.body.removeChild(loader);
    }
}

function countRemainingWordLength(textarea) {
    // Get the length of the text in the textarea.
    const wordLength = textarea.value.length;
    // Calculate the remaining word length.
    const remainingWordLength = 100 - wordLength;
    return remainingWordLength;
}
