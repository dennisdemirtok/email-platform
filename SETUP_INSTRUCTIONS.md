# Flattered Email Platform - Setup Instructions

## Local Development Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- MongoDB Atlas account

### Installation Steps

1. **Create your environment file:**
   ```bash
   cp env .env
   ```
   Then edit `.env` and fill in your credentials.

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Set proper permissions:**
   ```bash
   chmod -R 775 writable/
   ```

4. **Start the development server:**
   ```bash
   php spark serve
   ```

5. **Access the application:**
   - Open your browser and go to: `http://localhost:8080/login`
   - Login with the credentials you set in `.env`

## Environment Variables

The `.env` file requires these variables:

```ini
# Authentication
USER_APP = your_username
PASSWORD_APP = your_password

# MongoDB
MONGODB_URI = mongodb+srv://user:password@your-cluster.mongodb.net/?retryWrites=true&w=majority
DATABASE = EmailTrackerDB

# Resend API
RESEND_API_KEY = re_your_api_key
FROM_DOMAIN = yourdomain.com
CLOUD_FUNCTION_RESEND_QUEUE = https://your-region-your-project.cloudfunctions.net/resend-queue
CLOUD_FUNCTION_TOKEN = your_cloud_function_bearer_token
```

## Deployment to Google App Engine

1. Update `app.yaml` with your real credentials (or use Google Secret Manager)
2. Deploy:
   ```bash
   gcloud app deploy
   ```

## Troubleshooting

### Login not working?

1. **Check if .env file exists:**
   ```bash
   ls -la | grep .env
   ```

2. **Check environment variables are loaded:**
   - Look at the logs in `writable/logs/`

3. **Clear cache:**
   ```bash
   php spark cache:clear
   ```

### Common Issues

**"System configuration error. Please contact administrator."**
- USER_APP or PASSWORD_APP environment variables are not set
- Make sure `.env` exists and contains the credentials

**"Invalid login credentials"**
- Check that credentials in `.env` match what you're entering

**"Too many login attempts"**
- Wait 15 minutes or restart the server to reset

## Security Notes

- Never commit `.env` files to version control
- Use Google Secret Manager for production secrets
- Rotate credentials regularly
- The `env` file in the repo is a template with placeholder values only

---

Last Updated: February 2026
