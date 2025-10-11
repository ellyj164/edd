# Google Login Setup Guide

This guide provides step-by-step instructions for setting up Google OAuth 2.0 authentication for the FezaMarket e-commerce platform.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Create Google Cloud Project](#create-google-cloud-project)
3. [Enable Required APIs](#enable-required-apis)
4. [Create OAuth 2.0 Credentials](#create-oauth-20-credentials)
5. [Configure Environment Variables](#configure-environment-variables)
6. [Database Migration](#database-migration)
7. [Testing the Integration](#testing-the-integration)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have:
- A Google account
- Admin access to the FezaMarket application
- Access to the `.env` file on your server
- Database access for running migrations

---

## Create Google Cloud Project

### Step 1: Access Google Cloud Console
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Sign in with your Google account

### Step 2: Create a New Project
1. Click on the project dropdown at the top of the page
2. Click **"New Project"**
3. Enter project details:
   - **Project Name**: `FezaMarket OAuth` (or your preferred name)
   - **Organization**: Select your organization (if applicable)
   - **Location**: Select the appropriate location
4. Click **"Create"**
5. Wait for the project to be created (this may take a few seconds)
6. Select your newly created project from the project dropdown

---

## Enable Required APIs

### Step 3: Enable Google+ API
1. In the Google Cloud Console, navigate to **"APIs & Services" > "Library"**
2. Search for **"Google+ API"** (or **"People API"** for newer implementations)
3. Click on the API in the search results
4. Click **"Enable"**
5. Wait for the API to be enabled

> **Note**: The Google+ API provides access to user profile information. If it's deprecated, use the **People API** instead, which provides the same functionality.

---

## Create OAuth 2.0 Credentials

### Step 4: Configure OAuth Consent Screen
1. Navigate to **"APIs & Services" > "OAuth consent screen"**
2. Choose the user type:
   - **Internal**: For Google Workspace users only
   - **External**: For any Google account (recommended for public applications)
3. Click **"Create"**

### Step 5: Fill in OAuth Consent Screen Details
1. **App Information**:
   - **App name**: `FezaMarket`
   - **User support email**: Your support email (e.g., `support@fezamarket.com`)
   - **App logo**: Upload your app logo (optional but recommended)

2. **App domain** (Optional):
   - **Application home page**: `https://fezamarket.com`
   - **Application privacy policy link**: `https://fezamarket.com/privacy.php`
   - **Application terms of service link**: `https://fezamarket.com/terms.php`

3. **Authorized domains**:
   - Add `fezamarket.com` (without https://)
   - Add any other domains where your app is hosted

4. **Developer contact information**:
   - Add your email address

5. Click **"Save and Continue"**

### Step 6: Configure Scopes
1. Click **"Add or Remove Scopes"**
2. Select the following scopes:
   - `../auth/userinfo.email` - View your email address
   - `../auth/userinfo.profile` - See your personal info, including any personal info you've made publicly available
   - `openid` - Authenticate using OpenID Connect
3. Click **"Update"**
4. Click **"Save and Continue"**

### Step 7: Add Test Users (if using External type)
If you selected "External" as the user type and your app is in testing mode:
1. Click **"Add Users"**
2. Add email addresses of users who should be able to test the OAuth flow
3. Click **"Save and Continue"**

### Step 8: Create OAuth 2.0 Client ID
1. Navigate to **"APIs & Services" > "Credentials"**
2. Click **"Create Credentials"** > **"OAuth client ID"**
3. Select **Application type**: **"Web application"**
4. Configure the OAuth client:
   - **Name**: `FezaMarket Web Client`
   - **Authorized JavaScript origins**: 
     - Add `https://fezamarket.com`
     - Add `http://localhost` (for local development)
   - **Authorized redirect URIs**:
     - Add `https://fezamarket.com/auth/google-callback.php`
     - Add `http://localhost/auth/google-callback.php` (for local development)
5. Click **"Create"**

### Step 9: Save Your Credentials
After creating the OAuth client, a modal will appear with your credentials:
- **Client ID**: Starts with something like `123456789-abc...apps.googleusercontent.com`
- **Client Secret**: A random string like `GOCSPX-abc123...`

**Important**: Copy both values immediately and store them securely. You'll need these for the next step.

---

## Configure Environment Variables

### Step 10: Update the `.env` File
1. Open the `.env` file in your application root directory
2. Add or update the following variables with your credentials:

```bash
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your-client-id-here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-client-secret-here
GOOGLE_REDIRECT_URI=https://fezamarket.com/auth/google-callback.php
```

### Example Configuration

```bash
# Production Example
GOOGLE_CLIENT_ID=123456789-abc123def456.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-AbCdEf123456789
GOOGLE_REDIRECT_URI=https://fezamarket.com/auth/google-callback.php

# Local Development Example
GOOGLE_CLIENT_ID=987654321-xyz789.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-XyZ987654321
GOOGLE_REDIRECT_URI=http://localhost/auth/google-callback.php
```

> **Security Note**: 
> - Never commit the `.env` file to version control
> - Keep your Client Secret confidential
> - Use different credentials for development and production environments

---

## Database Migration

### Step 11: Run the Database Migration
The Google OAuth integration requires additional database fields to store OAuth provider information.

#### Option A: Using the Migration Script (Recommended)
```bash
php database/migrate.php
```

#### Option B: Manual SQL Execution
If you prefer to run the migration manually:

```sql
-- Add OAuth fields to users table
ALTER TABLE `users` 
ADD COLUMN `oauth_provider` VARCHAR(50) DEFAULT NULL COMMENT 'OAuth provider (google, facebook, etc.)',
ADD COLUMN `oauth_provider_id` VARCHAR(255) DEFAULT NULL COMMENT 'Unique ID from OAuth provider',
ADD COLUMN `oauth_token` TEXT DEFAULT NULL COMMENT 'OAuth access token (encrypted)',
ADD COLUMN `oauth_refresh_token` TEXT DEFAULT NULL COMMENT 'OAuth refresh token (encrypted)',
ADD INDEX `idx_oauth_provider` (`oauth_provider`),
ADD INDEX `idx_oauth_provider_id` (`oauth_provider_id`);

-- Make password optional for OAuth users
ALTER TABLE `users`
MODIFY COLUMN `pass_hash` VARCHAR(255) DEFAULT NULL;
```

### Step 12: Verify Database Changes
Run the following query to verify the new columns were added:

```sql
DESCRIBE users;
```

You should see the new columns:
- `oauth_provider`
- `oauth_provider_id`
- `oauth_token`
- `oauth_refresh_token`

---

## Testing the Integration

### Step 13: Test Google Login Flow

#### Test on Login Page
1. Navigate to `https://fezamarket.com/login.php`
2. You should see a **"Continue with Google"** button below the standard login form
3. Click the **"Continue with Google"** button
4. You should be redirected to Google's authentication page
5. Sign in with a Google account
6. Grant permissions when prompted
7. You should be redirected back to FezaMarket and logged in

#### Test on Registration Page
1. Navigate to `https://fezamarket.com/register.php`
2. You should see a **"Sign up with Google"** button
3. Click the button and follow the same flow as above
4. A new user account will be created automatically

### Step 14: Verify User Account
1. Log in to your database
2. Check the `users` table for the new OAuth user:

```sql
SELECT id, username, email, first_name, last_name, oauth_provider, oauth_provider_id
FROM users
WHERE oauth_provider = 'google'
ORDER BY created_at DESC
LIMIT 1;
```

You should see:
- `oauth_provider` set to `google`
- `oauth_provider_id` containing the Google user ID
- `pass_hash` will be `NULL` (OAuth users don't have passwords)

---

## Troubleshooting

### Common Issues and Solutions

#### Issue 1: "Google login is not properly configured"
**Cause**: Missing or incorrect environment variables.

**Solution**:
1. Verify that `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are set in `.env`
2. Ensure there are no extra spaces or quotes around the values
3. Restart your web server after updating `.env`

#### Issue 2: "redirect_uri_mismatch" Error
**Cause**: The redirect URI in your Google Cloud Console doesn't match the one in your `.env` file.

**Solution**:
1. Go to Google Cloud Console > APIs & Services > Credentials
2. Edit your OAuth 2.0 Client ID
3. Ensure the **Authorized redirect URIs** includes the exact URL from `GOOGLE_REDIRECT_URI` in your `.env` file
4. Make sure the protocol (http/https) and domain match exactly

#### Issue 3: "Invalid state parameter" Error
**Cause**: Session issues or CSRF token mismatch.

**Solution**:
1. Clear your browser cookies and cache
2. Try again in an incognito/private window
3. Verify that sessions are working correctly on your server
4. Check that `session_start()` is called before any OAuth flow

#### Issue 4: "Unable to retrieve user information from Google"
**Cause**: Insufficient API scopes or disabled APIs.

**Solution**:
1. Go to Google Cloud Console > APIs & Services > Library
2. Verify that Google+ API or People API is enabled
3. Check the OAuth consent screen scopes include:
   - `userinfo.email`
   - `userinfo.profile`
   - `openid`

#### Issue 5: Database Migration Fails
**Cause**: Table structure conflicts or insufficient permissions.

**Solution**:
1. Verify you have ALTER privileges on the database
2. Check if columns already exist:
   ```sql
   SHOW COLUMNS FROM users LIKE 'oauth%';
   ```
3. If columns exist, skip the migration
4. If there are errors, review the MySQL error log

#### Issue 6: "App blocked" or "This app isn't verified"
**Cause**: Your OAuth consent screen is in testing mode or not verified.

**Solution** (Testing Mode):
1. Add test users in Google Cloud Console > OAuth consent screen > Test users
2. Only test users can authenticate while the app is in testing mode

**Solution** (Production):
1. Submit your app for verification if needed
2. Or set the app to "Internal" if only used within your organization

---

## Security Best Practices

1. **Environment Variables**: Never commit `.env` files to version control
2. **HTTPS Only**: Always use HTTPS in production for OAuth redirects
3. **Rotate Secrets**: Periodically rotate your Client Secret
4. **Monitor Access**: Regularly review OAuth access in Google Cloud Console
5. **Limit Scopes**: Only request the minimum required scopes
6. **Test Users**: Use test users in development, not production accounts

---

## Additional Resources

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
- [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/)
- [league/oauth2-google Documentation](https://github.com/thephpleague/oauth2-google)

---

## Support

If you encounter any issues not covered in this guide:

1. Check the application logs in `/logs` directory
2. Review the Google Cloud Console logs
3. Contact support at support@fezamarket.com
4. Submit an issue on the project's GitHub repository

---

## Summary Checklist

- [ ] Created Google Cloud Project
- [ ] Enabled Google+ API or People API
- [ ] Configured OAuth consent screen
- [ ] Created OAuth 2.0 Client ID
- [ ] Copied Client ID and Client Secret
- [ ] Updated `.env` file with credentials
- [ ] Added authorized redirect URIs to Google Console
- [ ] Ran database migration
- [ ] Tested login flow on login page
- [ ] Tested registration flow on register page
- [ ] Verified user account in database
- [ ] Tested with a test user account

Once all items are checked, your Google OAuth integration is complete and ready for use!
