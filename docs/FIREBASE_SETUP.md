# Firebase Cloud Messaging Setup Guide

This guide explains how to configure Firebase Cloud Messaging (FCM) for push notifications in the Supply Chain Management API.

## Prerequisites

- Firebase project created in [Firebase Console](https://console.firebase.google.com/)
- Service account credentials JSON file downloaded from Firebase Console

## Configuration Methods

The application supports two methods for providing Firebase credentials:

### Method 1: Environment Variable (Recommended for Production)

1. Download your Firebase service account JSON from Firebase Console:
   - Go to Project Settings > Service Accounts
   - Click "Generate New Private Key"
   - Save the JSON file securely

2. Convert the JSON to a single-line string and add to `.env`:

```env
FIREBASE_CREDENTIALS_JSON='{"type":"service_account","project_id":"your-project-id",...}'
FIREBASE_PROJECT_ID=your-project-id
```

**Note:** Make sure to escape quotes properly or use single quotes to wrap the JSON string.

### Method 2: File-based Configuration (Recommended for Development)

1. Download your Firebase service account JSON file

2. Rename it to `firebase-auth.json`

3. Place the file in `storage/app/` directory:

```bash
cp /path/to/your-firebase-credentials.json storage/app/firebase-auth.json
```

4. Optionally set the project ID in `.env`:

```env
FIREBASE_PROJECT_ID=your-project-id
```

**Security Note:** Make sure `storage/app/firebase-auth.json` is in `.gitignore` to prevent committing credentials to version control.

## Configuration Priority

The application will use credentials in this order:

1. `FIREBASE_CREDENTIALS_JSON` environment variable (if set)
2. `storage/app/firebase-auth.json` file (fallback)
3. `null` (if neither is available)

## Testing Firebase Connection

After configuration, test the Firebase connection using the ping endpoint:

```bash
curl -X GET http://your-app-url/api/firebase/ping \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

Expected response:

```json
{
  "status": "success",
  "message": "Firebase connection successful",
  "data": {
    "project_id": "your-project-id",
    "client_email": "firebase-adminsdk-xxxxx@your-project-id.iam.gserviceaccount.com"
  }
}
```

## Sending Test Notifications

Send a test notification to verify FCM is working:

```bash
curl -X POST http://your-app-url/api/test-notification \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Notification",
    "body": "This is a test notification from API"
  }'
```

## Troubleshooting

### Error: "Firebase credentials not configured"

- Verify that either `FIREBASE_CREDENTIALS_JSON` is set in `.env` OR `storage/app/firebase-auth.json` exists
- Check that the JSON is valid and properly formatted
- Ensure the file has proper read permissions

### Error: "Invalid credentials"

- Verify the service account JSON is from the correct Firebase project
- Ensure the service account has the necessary permissions (Firebase Admin SDK)
- Check that the private key is not corrupted

### Error: "Failed to send notification"

- Verify the device token is valid and registered
- Check that FCM is enabled in your Firebase project
- Ensure the queue worker is running: `php artisan queue:work`

## Queue Configuration

FCM notifications are sent asynchronously using Laravel queues. Make sure your queue worker is running:

```bash
# Development
php artisan queue:work

# Production (using supervisor or systemd)
php artisan queue:work --daemon
```

## Security Best Practices

1. **Never commit credentials to version control**
   - Add `firebase-auth.json` to `.gitignore`
   - Use environment variables in production

2. **Restrict service account permissions**
   - Only grant necessary Firebase permissions
   - Use separate service accounts for different environments

3. **Rotate credentials regularly**
   - Generate new service account keys periodically
   - Revoke old keys after rotation

4. **Use environment-specific credentials**
   - Use different Firebase projects for development, staging, and production
   - Never use production credentials in development

## Related Documentation

- [Firebase Admin SDK Documentation](https://firebase.google.com/docs/admin/setup)
- [FCM Server Documentation](https://firebase.google.com/docs/cloud-messaging/server)
- [API Notification Endpoints](./API_NOTIFICATION.md)
