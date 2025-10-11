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
            preferredCountries: ['us', 'rw', 'ca', 'gb', 'au', 'de', 'fr'],
            separateDialCode: true,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        });
    }

    if (shippingPhoneField && window.intlTelInput) {
        shippingPhoneInput = window.intlTelInput(shippingPhoneField, {
            initialCountry: 'us',
            preferredCountries: ['us', 'rw', 'ca', 'gb', 'au', 'de', 'fr'],
            separateDialCode: true,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        });
    }

    // Comprehensive country list with flags, phone codes, and currencies
    const countries = [
        { code: 'AF', name: 'Afghanistan', flag: '🇦🇫', phone: '+93', currency: 'USD' },
        { code: 'AL', name: 'Albania', flag: '🇦🇱', phone: '+355', currency: 'USD' },
        { code: 'DZ', name: 'Algeria', flag: '🇩🇿', phone: '+213', currency: 'USD' },
        { code: 'AD', name: 'Andorra', flag: '🇦🇩', phone: '+376', currency: 'EUR' },
        { code: 'AO', name: 'Angola', flag: '🇦🇴', phone: '+244', currency: 'USD' },
        { code: 'AG', name: 'Antigua and Barbuda', flag: '🇦🇬', phone: '+1268', currency: 'USD' },
        { code: 'AR', name: 'Argentina', flag: '🇦🇷', phone: '+54', currency: 'USD' },
        { code: 'AM', name: 'Armenia', flag: '🇦🇲', phone: '+374', currency: 'USD' },
        { code: 'AU', name: 'Australia', flag: '🇦🇺', phone: '+61', currency: 'USD' },
        { code: 'AT', name: 'Austria', flag: '🇦🇹', phone: '+43', currency: 'EUR' },
        { code: 'AZ', name: 'Azerbaijan', flag: '🇦🇿', phone: '+994', currency: 'USD' },
        { code: 'BS', name: 'Bahamas', flag: '🇧🇸', phone: '+1242', currency: 'USD' },
        { code: 'BH', name: 'Bahrain', flag: '🇧🇭', phone: '+973', currency: 'USD' },
        { code: 'BD', name: 'Bangladesh', flag: '🇧🇩', phone: '+880', currency: 'USD' },
        { code: 'BB', name: 'Barbados', flag: '🇧🇧', phone: '+1246', currency: 'USD' },
        { code: 'BY', name: 'Belarus', flag: '🇧🇾', phone: '+375', currency: 'USD' },
        { code: 'BE', name: 'Belgium', flag: '🇧🇪', phone: '+32', currency: 'EUR' },
        { code: 'BZ', name: 'Belize', flag: '🇧🇿', phone: '+501', currency: 'USD' },
        { code: 'BJ', name: 'Benin', flag: '🇧🇯', phone: '+229', currency: 'USD' },
        { code: 'BT', name: 'Bhutan', flag: '🇧🇹', phone: '+975', currency: 'USD' },
        { code: 'BO', name: 'Bolivia', flag: '🇧🇴', phone: '+591', currency: 'USD' },
        { code: 'BA', name: 'Bosnia and Herzegovina', flag: '🇧🇦', phone: '+387', currency: 'USD' },
        { code: 'BW', name: 'Botswana', flag: '🇧🇼', phone: '+267', currency: 'USD' },
        { code: 'BR', name: 'Brazil', flag: '🇧🇷', phone: '+55', currency: 'USD' },
        { code: 'BN', name: 'Brunei', flag: '🇧🇳', phone: '+673', currency: 'USD' },
        { code: 'BG', name: 'Bulgaria', flag: '🇧🇬', phone: '+359', currency: 'EUR' },
        { code: 'BF', name: 'Burkina Faso', flag: '🇧🇫', phone: '+226', currency: 'USD' },
        { code: 'BI', name: 'Burundi', flag: '🇧🇮', phone: '+257', currency: 'USD' },
        { code: 'KH', name: 'Cambodia', flag: '🇰🇭', phone: '+855', currency: 'USD' },
        { code: 'CM', name: 'Cameroon', flag: '🇨🇲', phone: '+237', currency: 'USD' },
        { code: 'CA', name: 'Canada', flag: '🇨🇦', phone: '+1', currency: 'USD' },
        { code: 'CV', name: 'Cape Verde', flag: '🇨🇻', phone: '+238', currency: 'USD' },
        { code: 'CF', name: 'Central African Republic', flag: '🇨🇫', phone: '+236', currency: 'USD' },
        { code: 'TD', name: 'Chad', flag: '🇹🇩', phone: '+235', currency: 'USD' },
        { code: 'CL', name: 'Chile', flag: '🇨🇱', phone: '+56', currency: 'USD' },
        { code: 'CN', name: 'China', flag: '🇨🇳', phone: '+86', currency: 'USD' },
        { code: 'CO', name: 'Colombia', flag: '🇨🇴', phone: '+57', currency: 'USD' },
        { code: 'KM', name: 'Comoros', flag: '🇰🇲', phone: '+269', currency: 'USD' },
        { code: 'CG', name: 'Congo', flag: '🇨🇬', phone: '+242', currency: 'USD' },
        { code: 'CR', name: 'Costa Rica', flag: '🇨🇷', phone: '+506', currency: 'USD' },
        { code: 'HR', name: 'Croatia', flag: '🇭🇷', phone: '+385', currency: 'EUR' },
        { code: 'CU', name: 'Cuba', flag: '🇨🇺', phone: '+53', currency: 'USD' },
        { code: 'CY', name: 'Cyprus', flag: '🇨🇾', phone: '+357', currency: 'EUR' },
        { code: 'CZ', name: 'Czech Republic', flag: '🇨🇿', phone: '+420', currency: 'EUR' },
        { code: 'DK', name: 'Denmark', flag: '🇩🇰', phone: '+45', currency: 'EUR' },
        { code: 'DJ', name: 'Djibouti', flag: '🇩🇯', phone: '+253', currency: 'USD' },
        { code: 'DM', name: 'Dominica', flag: '🇩🇲', phone: '+1767', currency: 'USD' },
        { code: 'DO', name: 'Dominican Republic', flag: '🇩🇴', phone: '+1', currency: 'USD' },
        { code: 'EC', name: 'Ecuador', flag: '🇪🇨', phone: '+593', currency: 'USD' },
        { code: 'EG', name: 'Egypt', flag: '🇪🇬', phone: '+20', currency: 'USD' },
        { code: 'SV', name: 'El Salvador', flag: '🇸🇻', phone: '+503', currency: 'USD' },
        { code: 'GQ', name: 'Equatorial Guinea', flag: '🇬🇶', phone: '+240', currency: 'USD' },
        { code: 'ER', name: 'Eritrea', flag: '🇪🇷', phone: '+291', currency: 'USD' },
        { code: 'EE', name: 'Estonia', flag: '🇪🇪', phone: '+372', currency: 'EUR' },
        { code: 'ET', name: 'Ethiopia', flag: '🇪🇹', phone: '+251', currency: 'USD' },
        { code: 'FJ', name: 'Fiji', flag: '🇫🇯', phone: '+679', currency: 'USD' },
        { code: 'FI', name: 'Finland', flag: '🇫🇮', phone: '+358', currency: 'EUR' },
        { code: 'FR', name: 'France', flag: '🇫🇷', phone: '+33', currency: 'EUR' },
        { code: 'GA', name: 'Gabon', flag: '🇬🇦', phone: '+241', currency: 'USD' },
        { code: 'GM', name: 'Gambia', flag: '🇬🇲', phone: '+220', currency: 'USD' },
        { code: 'GE', name: 'Georgia', flag: '🇬🇪', phone: '+995', currency: 'USD' },
        { code: 'DE', name: 'Germany', flag: '🇩🇪', phone: '+49', currency: 'EUR' },
        { code: 'GH', name: 'Ghana', flag: '🇬🇭', phone: '+233', currency: 'USD' },
        { code: 'GR', name: 'Greece', flag: '🇬🇷', phone: '+30', currency: 'EUR' },
        { code: 'GD', name: 'Grenada', flag: '🇬🇩', phone: '+1473', currency: 'USD' },
        { code: 'GT', name: 'Guatemala', flag: '🇬🇹', phone: '+502', currency: 'USD' },
        { code: 'GN', name: 'Guinea', flag: '🇬🇳', phone: '+224', currency: 'USD' },
        { code: 'GW', name: 'Guinea-Bissau', flag: '🇬🇼', phone: '+245', currency: 'USD' },
        { code: 'GY', name: 'Guyana', flag: '🇬🇾', phone: '+592', currency: 'USD' },
        { code: 'HT', name: 'Haiti', flag: '🇭🇹', phone: '+509', currency: 'USD' },
        { code: 'HN', name: 'Honduras', flag: '🇭🇳', phone: '+504', currency: 'USD' },
        { code: 'HU', name: 'Hungary', flag: '🇭🇺', phone: '+36', currency: 'EUR' },
        { code: 'IS', name: 'Iceland', flag: '🇮🇸', phone: '+354', currency: 'USD' },
        { code: 'IN', name: 'India', flag: '🇮🇳', phone: '+91', currency: 'USD' },
        { code: 'ID', name: 'Indonesia', flag: '🇮🇩', phone: '+62', currency: 'USD' },
        { code: 'IR', name: 'Iran', flag: '🇮🇷', phone: '+98', currency: 'USD' },
        { code: 'IQ', name: 'Iraq', flag: '🇮🇶', phone: '+964', currency: 'USD' },
        { code: 'IE', name: 'Ireland', flag: '🇮🇪', phone: '+353', currency: 'EUR' },
        { code: 'IL', name: 'Israel', flag: '🇮🇱', phone: '+972', currency: 'USD' },
        { code: 'IT', name: 'Italy', flag: '🇮🇹', phone: '+39', currency: 'EUR' },
        { code: 'JM', name: 'Jamaica', flag: '🇯🇲', phone: '+1876', currency: 'USD' },
        { code: 'JP', name: 'Japan', flag: '🇯🇵', phone: '+81', currency: 'USD' },
        { code: 'JO', name: 'Jordan', flag: '🇯🇴', phone: '+962', currency: 'USD' },
        { code: 'KZ', name: 'Kazakhstan', flag: '🇰🇿', phone: '+7', currency: 'USD' },
        { code: 'KE', name: 'Kenya', flag: '🇰🇪', phone: '+254', currency: 'USD' },
        { code: 'KI', name: 'Kiribati', flag: '🇰🇮', phone: '+686', currency: 'USD' },
        { code: 'KW', name: 'Kuwait', flag: '🇰🇼', phone: '+965', currency: 'USD' },
        { code: 'KG', name: 'Kyrgyzstan', flag: '🇰🇬', phone: '+996', currency: 'USD' },
        { code: 'LA', name: 'Laos', flag: '🇱🇦', phone: '+856', currency: 'USD' },
        { code: 'LV', name: 'Latvia', flag: '🇱🇻', phone: '+371', currency: 'EUR' },
        { code: 'LB', name: 'Lebanon', flag: '🇱🇧', phone: '+961', currency: 'USD' },
        { code: 'LS', name: 'Lesotho', flag: '🇱🇸', phone: '+266', currency: 'USD' },
        { code: 'LR', name: 'Liberia', flag: '🇱🇷', phone: '+231', currency: 'USD' },
        { code: 'LY', name: 'Libya', flag: '🇱🇾', phone: '+218', currency: 'USD' },
        { code: 'LI', name: 'Liechtenstein', flag: '🇱🇮', phone: '+423', currency: 'USD' },
        { code: 'LT', name: 'Lithuania', flag: '🇱🇹', phone: '+370', currency: 'EUR' },
        { code: 'LU', name: 'Luxembourg', flag: '🇱🇺', phone: '+352', currency: 'EUR' },
        { code: 'MK', name: 'Macedonia', flag: '🇲🇰', phone: '+389', currency: 'USD' },
        { code: 'MG', name: 'Madagascar', flag: '🇲🇬', phone: '+261', currency: 'USD' },
        { code: 'MW', name: 'Malawi', flag: '🇲🇼', phone: '+265', currency: 'USD' },
        { code: 'MY', name: 'Malaysia', flag: '🇲🇾', phone: '+60', currency: 'USD' },
        { code: 'MV', name: 'Maldives', flag: '🇲🇻', phone: '+960', currency: 'USD' },
        { code: 'ML', name: 'Mali', flag: '🇲🇱', phone: '+223', currency: 'USD' },
        { code: 'MT', name: 'Malta', flag: '🇲🇹', phone: '+356', currency: 'EUR' },
        { code: 'MH', name: 'Marshall Islands', flag: '🇲🇭', phone: '+692', currency: 'USD' },
        { code: 'MR', name: 'Mauritania', flag: '🇲🇷', phone: '+222', currency: 'USD' },
        { code: 'MU', name: 'Mauritius', flag: '🇲🇺', phone: '+230', currency: 'USD' },
        { code: 'MX', name: 'Mexico', flag: '🇲🇽', phone: '+52', currency: 'USD' },
        { code: 'FM', name: 'Micronesia', flag: '🇫🇲', phone: '+691', currency: 'USD' },
        { code: 'MD', name: 'Moldova', flag: '🇲🇩', phone: '+373', currency: 'USD' },
        { code: 'MC', name: 'Monaco', flag: '🇲🇨', phone: '+377', currency: 'EUR' },
        { code: 'MN', name: 'Mongolia', flag: '🇲🇳', phone: '+976', currency: 'USD' },
        { code: 'ME', name: 'Montenegro', flag: '🇲🇪', phone: '+382', currency: 'EUR' },
        { code: 'MA', name: 'Morocco', flag: '🇲🇦', phone: '+212', currency: 'USD' },
        { code: 'MZ', name: 'Mozambique', flag: '🇲🇿', phone: '+258', currency: 'USD' },
        { code: 'MM', name: 'Myanmar', flag: '🇲🇲', phone: '+95', currency: 'USD' },
        { code: 'NA', name: 'Namibia', flag: '🇳🇦', phone: '+264', currency: 'USD' },
        { code: 'NR', name: 'Nauru', flag: '🇳🇷', phone: '+674', currency: 'USD' },
        { code: 'NP', name: 'Nepal', flag: '🇳🇵', phone: '+977', currency: 'USD' },
        { code: 'NL', name: 'Netherlands', flag: '🇳🇱', phone: '+31', currency: 'EUR' },
        { code: 'NZ', name: 'New Zealand', flag: '🇳🇿', phone: '+64', currency: 'USD' },
        { code: 'NI', name: 'Nicaragua', flag: '🇳🇮', phone: '+505', currency: 'USD' },
        { code: 'NE', name: 'Niger', flag: '🇳🇪', phone: '+227', currency: 'USD' },
        { code: 'NG', name: 'Nigeria', flag: '🇳🇬', phone: '+234', currency: 'USD' },
        { code: 'NO', name: 'Norway', flag: '🇳🇴', phone: '+47', currency: 'USD' },
        { code: 'OM', name: 'Oman', flag: '🇴🇲', phone: '+968', currency: 'USD' },
        { code: 'PK', name: 'Pakistan', flag: '🇵🇰', phone: '+92', currency: 'USD' },
        { code: 'PW', name: 'Palau', flag: '🇵🇼', phone: '+680', currency: 'USD' },
        { code: 'PA', name: 'Panama', flag: '🇵🇦', phone: '+507', currency: 'USD' },
        { code: 'PG', name: 'Papua New Guinea', flag: '🇵🇬', phone: '+675', currency: 'USD' },
        { code: 'PY', name: 'Paraguay', flag: '🇵🇾', phone: '+595', currency: 'USD' },
        { code: 'PE', name: 'Peru', flag: '🇵🇪', phone: '+51', currency: 'USD' },
        { code: 'PH', name: 'Philippines', flag: '🇵🇭', phone: '+63', currency: 'USD' },
        { code: 'PL', name: 'Poland', flag: '🇵🇱', phone: '+48', currency: 'EUR' },
        { code: 'PT', name: 'Portugal', flag: '🇵🇹', phone: '+351', currency: 'EUR' },
        { code: 'QA', name: 'Qatar', flag: '🇶🇦', phone: '+974', currency: 'USD' },
        { code: 'RO', name: 'Romania', flag: '🇷🇴', phone: '+40', currency: 'EUR' },
        { code: 'RU', name: 'Russia', flag: '🇷🇺', phone: '+7', currency: 'USD' },
        { code: 'RW', name: 'Rwanda', flag: '🇷🇼', phone: '+250', currency: 'RWF' },
        { code: 'KN', name: 'Saint Kitts and Nevis', flag: '🇰🇳', phone: '+1869', currency: 'USD' },
        { code: 'LC', name: 'Saint Lucia', flag: '🇱🇨', phone: '+1758', currency: 'USD' },
        { code: 'VC', name: 'Saint Vincent and the Grenadines', flag: '🇻🇨', phone: '+1784', currency: 'USD' },
        { code: 'WS', name: 'Samoa', flag: '🇼🇸', phone: '+685', currency: 'USD' },
        { code: 'SM', name: 'San Marino', flag: '🇸🇲', phone: '+378', currency: 'EUR' },
        { code: 'ST', name: 'Sao Tome and Principe', flag: '🇸🇹', phone: '+239', currency: 'USD' },
        { code: 'SA', name: 'Saudi Arabia', flag: '🇸🇦', phone: '+966', currency: 'USD' },
        { code: 'SN', name: 'Senegal', flag: '🇸🇳', phone: '+221', currency: 'USD' },
        { code: 'RS', name: 'Serbia', flag: '🇷🇸', phone: '+381', currency: 'USD' },
        { code: 'SC', name: 'Seychelles', flag: '🇸🇨', phone: '+248', currency: 'USD' },
        { code: 'SL', name: 'Sierra Leone', flag: '🇸🇱', phone: '+232', currency: 'USD' },
        { code: 'SG', name: 'Singapore', flag: '🇸🇬', phone: '+65', currency: 'USD' },
        { code: 'SK', name: 'Slovakia', flag: '🇸🇰', phone: '+421', currency: 'EUR' },
        { code: 'SI', name: 'Slovenia', flag: '🇸🇮', phone: '+386', currency: 'EUR' },
        { code: 'SB', name: 'Solomon Islands', flag: '🇸🇧', phone: '+677', currency: 'USD' },
        { code: 'SO', name: 'Somalia', flag: '🇸🇴', phone: '+252', currency: 'USD' },
        { code: 'ZA', name: 'South Africa', flag: '🇿🇦', phone: '+27', currency: 'USD' },
        { code: 'KR', name: 'South Korea', flag: '🇰🇷', phone: '+82', currency: 'USD' },
        { code: 'SS', name: 'South Sudan', flag: '🇸🇸', phone: '+211', currency: 'USD' },
        { code: 'ES', name: 'Spain', flag: '🇪🇸', phone: '+34', currency: 'EUR' },
        { code: 'LK', name: 'Sri Lanka', flag: '🇱🇰', phone: '+94', currency: 'USD' },
        { code: 'SD', name: 'Sudan', flag: '🇸🇩', phone: '+249', currency: 'USD' },
        { code: 'SR', name: 'Suriname', flag: '🇸🇷', phone: '+597', currency: 'USD' },
        { code: 'SZ', name: 'Swaziland', flag: '🇸🇿', phone: '+268', currency: 'USD' },
        { code: 'SE', name: 'Sweden', flag: '🇸🇪', phone: '+46', currency: 'EUR' },
        { code: 'CH', name: 'Switzerland', flag: '🇨🇭', phone: '+41', currency: 'USD' },
        { code: 'SY', name: 'Syria', flag: '🇸🇾', phone: '+963', currency: 'USD' },
        { code: 'TW', name: 'Taiwan', flag: '🇹🇼', phone: '+886', currency: 'USD' },
        { code: 'TJ', name: 'Tajikistan', flag: '🇹🇯', phone: '+992', currency: 'USD' },
        { code: 'TZ', name: 'Tanzania', flag: '🇹🇿', phone: '+255', currency: 'USD' },
        { code: 'TH', name: 'Thailand', flag: '🇹🇭', phone: '+66', currency: 'USD' },
        { code: 'TL', name: 'Timor-Leste', flag: '🇹🇱', phone: '+670', currency: 'USD' },
        { code: 'TG', name: 'Togo', flag: '🇹🇬', phone: '+228', currency: 'USD' },
        { code: 'TO', name: 'Tonga', flag: '🇹🇴', phone: '+676', currency: 'USD' },
        { code: 'TT', name: 'Trinidad and Tobago', flag: '🇹🇹', phone: '+1868', currency: 'USD' },
        { code: 'TN', name: 'Tunisia', flag: '🇹🇳', phone: '+216', currency: 'USD' },
        { code: 'TR', name: 'Turkey', flag: '🇹🇷', phone: '+90', currency: 'USD' },
        { code: 'TM', name: 'Turkmenistan', flag: '🇹🇲', phone: '+993', currency: 'USD' },
        { code: 'TV', name: 'Tuvalu', flag: '🇹🇻', phone: '+688', currency: 'USD' },
        { code: 'UG', name: 'Uganda', flag: '🇺🇬', phone: '+256', currency: 'USD' },
        { code: 'UA', name: 'Ukraine', flag: '🇺🇦', phone: '+380', currency: 'USD' },
        { code: 'AE', name: 'United Arab Emirates', flag: '🇦🇪', phone: '+971', currency: 'USD' },
        { code: 'GB', name: 'United Kingdom', flag: '🇬🇧', phone: '+44', currency: 'USD' },
        { code: 'US', name: 'United States', flag: '🇺🇸', phone: '+1', currency: 'USD' },
        { code: 'UY', name: 'Uruguay', flag: '🇺🇾', phone: '+598', currency: 'USD' },
        { code: 'UZ', name: 'Uzbekistan', flag: '🇺🇿', phone: '+998', currency: 'USD' },
        { code: 'VU', name: 'Vanuatu', flag: '🇻🇺', phone: '+678', currency: 'USD' },
        { code: 'VA', name: 'Vatican City', flag: '🇻🇦', phone: '+39', currency: 'EUR' },
        { code: 'VE', name: 'Venezuela', flag: '🇻🇪', phone: '+58', currency: 'USD' },
        { code: 'VN', name: 'Vietnam', flag: '🇻🇳', phone: '+84', currency: 'USD' },
        { code: 'YE', name: 'Yemen', flag: '🇾🇪', phone: '+967', currency: 'USD' },
        { code: 'ZM', name: 'Zambia', flag: '🇿🇲', phone: '+260', currency: 'USD' },
        { code: 'ZW', name: 'Zimbabwe', flag: '🇿🇼', phone: '+263', currency: 'USD' }
    ];

    // Populate country selects with searchable dropdown
    const billingCountrySelect = document.getElementById('billing_country');
    const shippingCountrySelect = document.getElementById('shipping_country');

    function populateCountrySelect(selectElement, defaultCode = 'US') {
        if (!selectElement) return;
        
        // Clear existing options except first (placeholder)
        selectElement.innerHTML = '<option value="">Select country...</option>';
        
        // Sort countries alphabetically by name
        const sortedCountries = [...countries].sort((a, b) => a.name.localeCompare(b.name));
        
        // Add all countries with flags
        sortedCountries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.code;
            option.textContent = `${country.flag} ${country.name}`;
            option.dataset.phone = country.phone;
            option.dataset.currency = country.currency;
            if (country.code === defaultCode) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    }
    
    // Function to update phone input when country changes
    function updatePhoneCountryCode(countryCode, phoneInputInstance) {
        if (!phoneInputInstance) return;
        
        const country = countries.find(c => c.code === countryCode);
        if (country && phoneInputInstance.setCountry) {
            phoneInputInstance.setCountry(countryCode.toLowerCase());
        }
    }
    
    // Function to update currency display when country changes
    function updateCurrency(countryCode) {
        const country = countries.find(c => c.code === countryCode);
        if (!country) return;
        
        // Display currency info to user
        const currencyNote = document.getElementById('currency-note');
        if (currencyNote) {
            let currencySymbol = '$';
            if (country.currency === 'EUR') currencySymbol = '€';
            if (country.currency === 'RWF') currencySymbol = 'FRw';
            
            currencyNote.textContent = `Prices will be shown in ${country.currency} (${currencySymbol})`;
            currencyNote.style.display = 'block';
        }
    }

    populateCountrySelect(billingCountrySelect, 'US');
    populateCountrySelect(shippingCountrySelect, 'US');
    
    // Listen for country selection changes to update phone code and currency
    if (billingCountrySelect) {
        billingCountrySelect.addEventListener('change', function() {
            updatePhoneCountryCode(this.value, billingPhoneInput);
            updateCurrency(this.value);
        });
    }
    
    if (shippingCountrySelect) {
        shippingCountrySelect.addEventListener('change', function() {
            updatePhoneCountryCode(this.value, shippingPhoneInput);
        });
    }

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
