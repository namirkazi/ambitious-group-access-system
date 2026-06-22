# VisitorHub — Visitor Management System
## Setup Instructions for XAMPP

### 1. Copy Files
Place the entire `visitor_system` folder inside your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\visitor_system\
```

### 2. Create the Database
1. Open **phpMyAdmin** → http://localhost/phpmyadmin
2. Click **Import** in the top menu
3. Upload `database.sql` from this folder
4. Click **Go** — the `visitor_management` database will be created with all tables and sample hosts

### 3. Configure Database (if needed)
Edit `includes/config.php` if your XAMPP uses a different username or password:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');   // Default XAMPP = empty password
define('DB_NAME', 'visitor_management');
```

### 4. Camera Permissions
- Access via **http://localhost/visitor_system** (not file://)
- Browser will ask for camera access — click **Allow**
- Chrome/Edge/Firefox all supported

### 5. Folder Permissions
Make sure the uploads folder is writable:
```
visitor_system/assets/uploads/photos/
```
On Windows with XAMPP this is automatic.

---

## How It Works

### Check-In Flow
1. Enter visitor's **phone number** → system auto-looks them up
2. **Returning visitor**: details pre-fill, just choose host + purpose
3. **New visitor**: fill name, email, phone details
4. Capture a **webcam photo** (optional but recommended)
5. Select **who they're meeting** and **department** from the dropdown
6. Describe the **purpose of visit**
7. Click **Register Visit** → system prints a **Badge Number**

### Auto-Recognition
- When a phone number is entered, the system instantly searches the database
- If found, the visitor's name, email, and photo are displayed
- They only need to fill in the visit-specific fields (host + purpose)

### Admin Dashboard
Visit: **http://localhost/visitor_system/admin.php**
- Live stats: total visitors, today's count, who's currently inside
- Full visit log table with photos, check-in/out times, badge numbers
- Search/filter by name, phone, or host

---

## File Structure
```
visitor_system/
├── index.php               ← Main check-in screen
├── admin.php               ← Admin dashboard
├── database.sql            ← Run this first in phpMyAdmin
├── README.md               ← This file
├── includes/
│   └── config.php          ← DB connection settings
├── api/
│   ├── lookup_visitor.php  ← Phone number lookup
│   ├── register_visit.php  ← Save new visitor + visit log
│   ├── get_hosts.php       ← Host list for dropdown
│   └── checkout.php        ← Mark visitor as checked out
└── assets/
    └── uploads/photos/     ← Webcam photos saved here
```
