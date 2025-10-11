/**
 * Stripe Checkout Integration - Enhanced
 * Handles separate Stripe Elements (cardNumber, cardExpiry, cardCvc),
 * intl-tel-input for phone fields, country dropdown population,
 * and save-for-future billing functionality
 */

(function() {
    'use strict';

    // Get Stripe publishable key from window
    const stripePublishableKey = window.STRIPE_PUBLISHABLE_KEY;
    if (!stripePublishableKey) {
        console.error('Stripe publishable key not configured');
        return;
    }

    // Initialize Stripe
    const stripe = Stripe(stripePublishableKey);
    const elements = stripe.elements();

    // Stripe Elements styling
    const elementStyles = {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // Create separate card elements
    const cardNumberElement = elements.create('cardNumber', {
        style: elementStyles,
        placeholder: '1234 5678 9012 3456'
    });

    const cardExpiryElement = elements.create('cardExpiry', {
        style: elementStyles
    });

    const cardCvcElement = elements.create('cardCvc', {
        style: elementStyles
    });

    // Mount card elements to their containers
    cardNumberElement.mount('#stripe-card-number');
    cardExpiryElement.mount('#stripe-card-expiry');
    cardCvcElement.mount('#stripe-card-cvc');

    // Handle real-time validation errors
    const displayError = document.getElementById('stripe-card-errors');
    
    function handleCardError(event) {
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    }

    cardNumberElement.on('change', handleCardError);
    cardExpiryElement.on('change', handleCardError);
    cardCvcElement.on('change', handleCardError);

    // Initialize intl-tel-input for phone fields
    let billingPhoneInput = null;
    let shippingPhoneInput = null;

    const billingPhoneField = document.getElementById('billing_phone');
    const shippingPhoneField = document.getElementById('shipping_phone');

    if (billingPhoneField && window.intlTelInput) {
        billingPhoneInput = window.intlTelInput(billingPhoneField, {
            initialCountry: 'us',
            preferredCountries: ['us', 'ca', 'gb', 'au'],
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        });
    }

    if (shippingPhoneField && window.intlTelInput) {
        shippingPhoneInput = window.intlTelInput(shippingPhoneField, {
            initialCountry: 'us',
            preferredCountries: ['us', 'ca', 'gb', 'au'],
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        });
    }

    // Populate country dropdowns with all countries
    const countries = [
        { code: 'US', name: 'United States' },
        { code: 'CA', name: 'Canada' },
        { code: 'GB', name: 'United Kingdom' },
        { code: 'AU', name: 'Australia' },
        { code: 'AF', name: 'Afghanistan' },
        { code: 'AL', name: 'Albania' },
        { code: 'DZ', name: 'Algeria' },
        { code: 'AR', name: 'Argentina' },
        { code: 'AM', name: 'Armenia' },
        { code: 'AT', name: 'Austria' },
        { code: 'AZ', name: 'Azerbaijan' },
        { code: 'BH', name: 'Bahrain' },
        { code: 'BD', name: 'Bangladesh' },
        { code: 'BY', name: 'Belarus' },
        { code: 'BE', name: 'Belgium' },
        { code: 'BZ', name: 'Belize' },
        { code: 'BO', name: 'Bolivia' },
        { code: 'BA', name: 'Bosnia and Herzegovina' },
        { code: 'BR', name: 'Brazil' },
        { code: 'BG', name: 'Bulgaria' },
        { code: 'KH', name: 'Cambodia' },
        { code: 'CM', name: 'Cameroon' },
        { code: 'CL', name: 'Chile' },
        { code: 'CN', name: 'China' },
        { code: 'CO', name: 'Colombia' },
        { code: 'CR', name: 'Costa Rica' },
        { code: 'HR', name: 'Croatia' },
        { code: 'CY', name: 'Cyprus' },
        { code: 'CZ', name: 'Czech Republic' },
        { code: 'DK', name: 'Denmark' },
        { code: 'DO', name: 'Dominican Republic' },
        { code: 'EC', name: 'Ecuador' },
        { code: 'EG', name: 'Egypt' },
        { code: 'SV', name: 'El Salvador' },
        { code: 'EE', name: 'Estonia' },
        { code: 'ET', name: 'Ethiopia' },
        { code: 'FI', name: 'Finland' },
        { code: 'FR', name: 'France' },
        { code: 'GE', name: 'Georgia' },
        { code: 'DE', name: 'Germany' },
        { code: 'GH', name: 'Ghana' },
        { code: 'GR', name: 'Greece' },
        { code: 'GT', name: 'Guatemala' },
        { code: 'HN', name: 'Honduras' },
        { code: 'HK', name: 'Hong Kong' },
        { code: 'HU', name: 'Hungary' },
        { code: 'IS', name: 'Iceland' },
        { code: 'IN', name: 'India' },
        { code: 'ID', name: 'Indonesia' },
        { code: 'IR', name: 'Iran' },
        { code: 'IQ', name: 'Iraq' },
        { code: 'IE', name: 'Ireland' },
        { code: 'IL', name: 'Israel' },
        { code: 'IT', name: 'Italy' },
        { code: 'JM', name: 'Jamaica' },
        { code: 'JP', name: 'Japan' },
        { code: 'JO', name: 'Jordan' },
        { code: 'KZ', name: 'Kazakhstan' },
        { code: 'KE', name: 'Kenya' },
        { code: 'KR', name: 'Korea, South' },
        { code: 'KW', name: 'Kuwait' },
        { code: 'LV', name: 'Latvia' },
        { code: 'LB', name: 'Lebanon' },
        { code: 'LT', name: 'Lithuania' },
        { code: 'LU', name: 'Luxembourg' },
        { code: 'MY', name: 'Malaysia' },
        { code: 'MT', name: 'Malta' },
        { code: 'MX', name: 'Mexico' },
        { code: 'MD', name: 'Moldova' },
        { code: 'MA', name: 'Morocco' },
        { code: 'NL', name: 'Netherlands' },
        { code: 'NZ', name: 'New Zealand' },
        { code: 'NG', name: 'Nigeria' },
        { code: 'NO', name: 'Norway' },
        { code: 'OM', name: 'Oman' },
        { code: 'PK', name: 'Pakistan' },
        { code: 'PA', name: 'Panama' },
        { code: 'PY', name: 'Paraguay' },
        { code: 'PE', name: 'Peru' },
        { code: 'PH', name: 'Philippines' },
        { code: 'PL', name: 'Poland' },
        { code: 'PT', name: 'Portugal' },
        { code: 'QA', name: 'Qatar' },
        { code: 'RO', name: 'Romania' },
        { code: 'RU', name: 'Russia' },
        { code: 'SA', name: 'Saudi Arabia' },
        { code: 'SN', name: 'Senegal' },
        { code: 'RS', name: 'Serbia' },
        { code: 'SG', name: 'Singapore' },
        { code: 'SK', name: 'Slovakia' },
        { code: 'SI', name: 'Slovenia' },
        { code: 'ZA', name: 'South Africa' },
        { code: 'ES', name: 'Spain' },
        { code: 'LK', name: 'Sri Lanka' },
        { code: 'SE', name: 'Sweden' },
        { code: 'CH', name: 'Switzerland' },
        { code: 'TW', name: 'Taiwan' },
        { code: 'TZ', name: 'Tanzania' },
        { code: 'TH', name: 'Thailand' },
        { code: 'TN', name: 'Tunisia' },
        { code: 'TR', name: 'Turkey' },
        { code: 'UG', name: 'Uganda' },
        { code: 'UA', name: 'Ukraine' },
        { code: 'AE', name: 'United Arab Emirates' },
        { code: 'UY', name: 'Uruguay' },
        { code: 'VE', name: 'Venezuela' },
        { code: 'VN', name: 'Vietnam' },
        { code: 'YE', name: 'Yemen' },
        { code: 'ZM', name: 'Zambia' },
        { code: 'ZW', name: 'Zimbabwe' }
    ];

    // Populate country selects
    const billingCountrySelect = document.getElementById('billing_country');
    const shippingCountrySelect = document.getElementById('shipping_country');

    function populateCountrySelect(selectElement, defaultCode = 'US') {
        if (!selectElement) return;
        
        // Clear existing options except first (placeholder)
        selectElement.innerHTML = '<option value="">Select country...</option>';
        
        // Add all countries
        countries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.code;
            option.textContent = country.name;
            if (country.code === defaultCode) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    }

    populateCountrySelect(billingCountrySelect, 'US');
    populateCountrySelect(shippingCountrySelect, 'US');

    // Handle shipping address toggle
    const sameAsBillingCheckbox = document.getElementById('same_as_billing');
    const shippingAddressFields = document.getElementById('shipping-address-fields');
    
    if (sameAsBillingCheckbox && shippingAddressFields) {
        sameAsBillingCheckbox.addEventListener('change', function() {
            if (this.checked) {
                shippingAddressFields.style.display = 'none';
                // Clear required attribute from shipping fields
                shippingAddressFields.querySelectorAll('input, select').forEach(input => {
                    input.removeAttribute('required');
                });
            } else {
                shippingAddressFields.style.display = 'block';
                // Add required attribute to shipping fields (except optional ones)
                const requiredFields = ['shipping_name', 'shipping_phone', 'shipping_line1', 
                                       'shipping_city', 'shipping_state', 'shipping_postal', 'shipping_country'];
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.setAttribute('required', 'required');
                    }
                });
            }
        });
    }

    // Handle form submission
    const form = document.getElementById('checkout-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    const paymentMessage = document.getElementById('payment-message');

    if (!form) {
        console.error('Checkout form not found');
        return;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Disable submit button to prevent double submission
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'block';
        paymentMessage.style.display = 'none';
        
        try {
            // Check if save for future is selected
            const saveForFuture = document.getElementById('save_for_future')?.checked || false;
            
            // Step 1: Create PaymentIntent on server
            console.log('Creating payment intent with save_for_future:', saveForFuture);
            const response = await fetch('/api/create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    save_for_future: saveForFuture
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Server error' }));
                throw new Error(errorData.error || 'Failed to create payment intent');
            }
            
            const data = await response.json();
            
            if (!data.success || !data.clientSecret) {
                throw new Error(data.error || 'Invalid server response');
            }
            
            console.log('PaymentIntent created:', data.paymentIntentId);
            
            // Step 2: Collect billing and shipping details
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const billingLine1 = document.getElementById('billing_line1');
            const billingLine2 = document.getElementById('billing_line2');
            const billingCity = document.getElementById('billing_city');
            const billingState = document.getElementById('billing_state');
            const billingPostal = document.getElementById('billing_postal');
            const billingCountry = document.getElementById('billing_country');
            
            // Get full phone number with country code
            const billingPhone = billingPhoneInput ? 
                billingPhoneInput.getNumber() : 
                billingPhoneField.value;
            
            const billing_details = {
                name: nameInput.value,
                email: emailInput.value,
                phone: billingPhone,
                address: {
                    line1: billingLine1.value,
                    line2: billingLine2.value || undefined,
                    city: billingCity.value,
                    state: billingState.value,
                    postal_code: billingPostal.value,
                    country: billingCountry.value
                }
            };

            // Prepare shipping details
            let shipping = null;
            if (!sameAsBillingCheckbox.checked) {
                const shippingName = document.getElementById('shipping_name');
                const shippingLine1 = document.getElementById('shipping_line1');
                const shippingLine2 = document.getElementById('shipping_line2');
                const shippingCity = document.getElementById('shipping_city');
                const shippingState = document.getElementById('shipping_state');
                const shippingPostal = document.getElementById('shipping_postal');
                const shippingCountry = document.getElementById('shipping_country');
                
                // Get full shipping phone number with country code
                const shippingPhone = shippingPhoneInput ? 
                    shippingPhoneInput.getNumber() : 
                    shippingPhoneField.value;
                
                shipping = {
                    name: shippingName.value,
                    phone: shippingPhone,
                    address: {
                        line1: shippingLine1.value,
                        line2: shippingLine2.value || undefined,
                        city: shippingCity.value,
                        state: shippingState.value,
                        postal_code: shippingPostal.value,
                        country: shippingCountry.value
                    }
                };
            } else {
                // Use billing details for shipping
                shipping = {
                    name: nameInput.value,
                    phone: billingPhone,
                    address: {
                        line1: billingLine1.value,
                        line2: billingLine2.value || undefined,
                        city: billingCity.value,
                        state: billingState.value,
                        postal_code: billingPostal.value,
                        country: billingCountry.value
                    }
                };
            }
            
            // Step 3: Confirm payment with Stripe
            console.log('Confirming payment...');
            const {error, paymentIntent} = await stripe.confirmCardPayment(
                data.clientSecret,
                {
                    payment_method: {
                        card: cardNumberElement,
                        billing_details: billing_details
                    },
                    shipping: shipping
                }
            );
            
            if (error) {
                // Show error to customer
                console.error('Payment error:', error);
                throw new Error(error.message);
            }
            
            // Payment successful!
            console.log('Payment successful:', paymentIntent.id);
            
            // Redirect to confirmation page
            window.location.href = '/order-confirmation.php?payment_intent=' + paymentIntent.id;
            
        } catch (error) {
            // Show error message
            console.error('Checkout error:', error);
            paymentMessage.textContent = error.message || 'An error occurred during checkout';
            paymentMessage.style.display = 'block';
            
            // Re-enable submit button
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        }
    });
})();
