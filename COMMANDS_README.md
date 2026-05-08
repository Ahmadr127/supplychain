# Artisan Commands Documentation

This project includes custom Artisan commands to help with system maintenance and development.

## Cleaning Requests

### `app:clean-requests`

This command is used to completely wipe all approval requests and their associated data from the system. It is particularly useful for resetting the application state during development or before a clean production deployment.

#### What it does:
1. **Clears Database Tables**: Truncates all tables related to requests, including:
   - `approval_requests`
   - `approval_request_items`
   - `approval_item_steps`
   - `purchasing_items`
   - `purchasing_item_vendors`
   - `purchasing_item_vendor_trials`
   - `notifications`
   - `capex_allocations`
2. **Resets CapEx Budget**: Sets `used_amount` and `pending_amount` to `0` for all `capex_items` and resets their status to `available`.
3. **Deletes Storage Files**: Permanently removes uploaded files from the `public` storage disk, specifically the following directories:
   - `fs_documents/`
   - `approval_items/`

#### Usage:

```bash
php artisan app:clean-requests
```

By default, the command will ask for confirmation before proceeding.

#### Options:

- `--force`: Runs the command without asking for confirmation. Use this with caution!

```bash
php artisan app:clean-requests --force
```

---

> [!CAUTION]
> **Warning**: This action is irreversible. All transaction data related to requests will be permanently lost.
