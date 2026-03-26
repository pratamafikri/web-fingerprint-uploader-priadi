# Web Fingerprint Taking System - Complete Documentation

## Table of Contents
1. [High-Level Overview](#high-level-overview)
2. [Architecture](#architecture)
3. [Code Structure](#code-structure)
4. [API Reference](#api-reference)
5. [Setup & Installation](#setup--installation)
6. [Usage Guide](#usage-guide)
7. [Best Practices](#best-practices)

---

## High-Level Overview

### Project Purpose
**Web Fingerprint Taking System** is a web-based application for collecting and managing fingerprint data from participants. It provides:
- An intuitive web interface for group and participant management
- Fingerprint image capture and upload functionality (all 10 fingers)
- RESTful API for programmatic access
- Integration with the Priadi REST API backend for persistent storage

### Main Features
✅ **Group Management** - Create, update, delete participant groups  
✅ **Participant Management** - Register participants with personal information  
✅ **Fingerprint Capture** - Web-based interface for uploading fingerprint images (left/right hands, 5 fingers each)  
✅ **Data Persistence** - All data synced with external Priadi REST API  
✅ **Responsive UI** - Bootstrap 5.3.8-based modern interface  
✅ **RESTful API** - Complete API endpoints for all operations  
✅ **Caching** - Intelligent caching layer for improved performance  

### Target Users & Use Cases
- **Organizations** collecting biometric data for identification systems
- **Government agencies** managing citizen fingerprint records
- **HR departments** building employee fingerprint databases
- **Law enforcement** conducting fingerprint identification
- **Developers** integrating fingerprint systems via REST API

---

## Architecture

### System Design

```
┌─────────────────────────────────────────────────────────────┐
│                     Web Browser Client                       │
│         (Bootstrap 5, jQuery, DataTables, Axios)             │
└────────────────────────────┬────────────────────────────────┘
                             │ HTTP/AJAX
┌────────────────────────────▼────────────────────────────────┐
│              CodeIgniter 4 Web Application                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │            Routes Configuration                      │   │
│  │  - Web Routes (UI)                                   │   │
│  │  - API Routes (/api/*)                               │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │            Controllers                               │   │
│  │  - HomeController (view pages)                       │   │
│  │  - GroupController (manage groups)                   │   │
│  │  - ParticipantController (manage participants)       │   │
│  │  - ApiController (REST API endpoints)                │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │            Cache Layer                               │   │
│  │  (File-based cache, 1-hour TTL)                      │   │
│  └──────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────┘
                             │ HTTP (Guzzle)
┌────────────────────────────▼────────────────────────────────┐
│           Priadi REST API (External Service)                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  - Groups Management                                 │   │
│  │  - Participants Management                           │   │
│  │  - Fingerprint Images Storage                        │   │
│  └──────────────────────────────────────────────────────┘   │
│           (MySQLi / Remote Database)                        │
└─────────────────────────────────────────────────────────────┘
```

### Component Interactions

1. **Client Layer** - Browser requests sent via Axios/jQuery
2. **Web Server** - CodeIgniter 4 routes requests to appropriate controllers
3. **Controller Layer** - Business logic and API orchestration
4. **Cache Layer** - Reduces API calls with intelligent caching
5. **API Client Layer** - Wraps Guzzle HTTP for Priadi API communication
6. **External API** - Persists all data to Priadi backend

### Data Flow for Participant Fingerprint Upload

```
User clicks fingerprint image
        ↓
File input dialog opened
        ↓
Image selected → FileReader converts to Base64
        ↓
POST /api/participant/update_finger
        ↓
ApiController::saveFingerParticipant()
        ↓
Validation: JPG/PNG, 2MB max
        ↓
Convert to data URI: data:image/jpeg;base64,...
        ↓
POST to Priadi API: /person/finger/{id}
        ↓
Cache invalidated for participant
        ↓
Response: JSON status
        ↓
UI shows success badge with checkmark
```

---

## Code Structure

### Directory Overview

| Directory | Purpose |
|-----------|---------|
| **app/** | Application source code (MVC) |
| **app/Config/** | Configuration files (routes, database, app settings) |
| **app/Controllers/** | Request handlers (HomeController, ApiController, etc.) |
| **app/Views/** | HTML templates (layout, pages) |
| **app/Models/** | Data models (empty - uses external API) |
| **app/Database/** | Migrations and seeds (empty - uses external API) |
| **app/Helpers/** | Helper functions (empty - not needed) |
| **app/Libraries/** | Custom libraries (empty - not needed) |
| **public/** | Web-accessible folder (index.php entry point) |
| **vendor/** | Composer dependencies |
| **writable/** | Cache, logs, sessions, uploads (must be writable by web server) |
| **tests/** | PHPUnit tests |

### Key Files

#### [app/Config/Routes.php](app/Config/Routes.php)
**Responsibility:** URL routing and endpoint mapping

**Web Routes (UI):**
```
GET  /                      → Home page
GET  /group                 → Group management page
GET  /participant           → Participant list page
GET  /participant/{id}      → Participant detail (fingerprint capture)
```

**API Routes (RESTful):**
```
GET    /api/group                    → List all groups
POST   /api/group/save               → Create/update group
POST   /api/group/delete             → Delete group

GET    /api/participant              → List participants (filtered)
GET    /api/participant/{id}         → Get participant details
POST   /api/participant/save          → Create/update participant
POST   /api/participant/delete        → Delete participant
POST   /api/participant/update_finger → Upload fingerprint image
```

#### [app/Controllers/ApiController.php](app/Controllers/ApiController.php)
**Responsibility:** Core API endpoint handler for all operations

**Key Methods:**

| Method | HTTP | Purpose |
|--------|------|---------|
| `__construct()` | - | Initialize ApiClient with credentials from .env |
| `getGroups()` | GET | Fetch all groups (cached 1 hour) |
| `saveGroup()` | POST | Create/update group |
| `deleteGroup()` | POST | Delete group, invalidate cache |
| `getParticipantByGroup()` | GET | List participants for a group |
| `getParticipantById($id)` | GET | Get single participant details |
| `saveParticipant()` | POST | Create/update participant |
| `deleteParticipant()` | POST | Delete participant |
| `saveFingerParticipant()` | POST | Upload fingerprint image |

**Important Implementation Details:**

```php
// Finger name mapping (user-friendly → API)
thumb     → thumb
index     → forefinger
middle    → middlefinger
ring      → thirdfinger
pinky     → littlefinger

// Field naming pattern
image_{hand}_{finger}  (e.g., image_left_thumb)

// Image encoding
Data URI: data:image/jpeg;base64,{base64_data}

// Validation
- File types: JPG, PNG only
- Max size: 2MB
- Returns JSON with error/success messages
```

#### [app/Controllers/GroupController.php](app/Controllers/GroupController.php)
**Responsibility:** Group management UI

| Method | Purpose |
|--------|---------|
| `index()` | Render group management page (group.php) |

#### [app/Controllers/ParticipantController.php](app/Controllers/ParticipantController.php)
**Responsibility:** Participant management UI

| Method | Purpose |
|--------|---------|
| `index()` | Render participant list (filtered by group) |
| `view($id)` | Render participant detail with fingerprint capture interface |

#### [app/Controllers/HomeController.php](app/Controllers/HomeController.php)
**Responsibility:** Homepage

| Method | Purpose |
|--------|---------|
| `index()` | Render welcome page with navigation |

### Views

#### [app/Views/layout.php](app/Views/layout.php)
Master template for all pages. Includes:
- Bootstrap 5.3.8 CSS/JS
- Navigation bar
- Container for main content
- Section rendering system

**Template Sections:**
- `content` - Main page content
- `style` - Page-specific CSS
- `script` - Page-specific JavaScript

#### [app/Views/home.php](app/Views/home.php)
Welcome page with navigation cards to main sections.

#### [app/Views/group.php](app/Views/group.php)
Group management interface with:
- DataTable listing all groups
- Add/Edit/Delete modals
- AJAX operations to API endpoints

#### [app/Views/participant.php](app/Views/participant.php)
Participant list with:
- Filtered DataTable (by group)
- Add/Edit/Delete modals
- Link to detailed participant view

#### [app/Views/participant_detail.php](app/Views/participant_detail.php)
Fingerprint capture interface with:
- Participant information display
- 10 fingerprint image placeholders
- File upload handling for each finger
- Progress indicators and success badges

### Configuration

#### [app/Config/Database.php](app/Config/Database.php)
Database configuration (uses MySQLi by default). Configure via `.env`:
```php
database.default.hostname = localhost
database.default.username = root
database.default.password = password
database.default.database = fingerprint_db
```

**Note:** Not required if using only external Priadi API

#### [app/Config/App.php](app/Config/App.php)
Application settings:
```php
baseURL = 'http://localhost:8080/'
indexPage = 'index.php'
defaultLocale = 'en'
appTimezone = 'UTC'
charset = 'UTF-8'
```

---

## API Reference

### Authentication
All API endpoints require authentication via environment variables. The `ApiClient` automatically includes credentials in requests.

**Required .env variables:**
```bash
P2F_API_KEY=your_api_key
P2F_API_SECRET=your_api_secret
P2F_API_JWT=false  # Set to true if using JWT instead
```

### Response Format
All API endpoints return JSON:
```json
{
  "status": "success|error",
  "data": { /* response data */ },
  "message": "Human-readable message"
}
```

### Endpoints Detail

#### 📊 GROUP ENDPOINTS

##### GET `/api/group`
Fetch all groups

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "instance_group_id": 1,
      "instance_group_name": "Group A",
      "address": "123 Main St"
    },
    {
      "instance_group_id": 2,
      "instance_group_name": "Group B",
      "address": "456 Oak Ave"
    }
  ]
}
```

**Caching:** 1 hour

##### POST `/api/group/save`
Create or update a group

**Request:**
```json
{
  "instance_group_id": 1,      // omit for new group
  "instance_group_name": "Group Name",
  "address": "123 Main Street"
}
```

**Response:**
```json
{
  "status": "success",
  "data": { "instance_group_id": 1 },
  "message": "Group saved successfully"
}
```

**Cache:** Invalidates `instance_groups`

##### POST `/api/group/delete`
Delete a group

**Request:**
```json
{
  "instance_group_id": 1
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Group deleted successfully"
}
```

**Cache:** Invalidates groups and related participants caches

---

#### 👥 PARTICIPANT ENDPOINTS

##### GET `/api/participant?instance_group_id=1`
Fetch participants for a group

**Query Parameters:**
- `instance_group_id` (required) - Group ID to filter by

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "participant_id": 101,
      "instance_group_id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "birthdate": "1990-01-15",
      "phone": "555-0123"
    }
  ]
}
```

**Caching:** 1 hour (per group)

##### GET `/api/participant/{id}`
Fetch participant details with fingerprint information

**URL Parameters:**
- `id` - Participant ID

**Response:**
```json
{
  "status": "success",
  "data": {
    "participant_id": 101,
    "instance_group_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "birthdate": "1990-01-15",
    "phone": "555-0123",
    "image_left_thumb": "data:image/jpeg;base64,...",
    "image_left_forefinger": "data:image/jpeg;base64,...",
    "image_left_middlefinger": "data:image/jpeg;base64,...",
    "image_left_thirdfinger": "data:image/jpeg;base64,...",
    "image_left_littlefinger": "data:image/jpeg;base64,...",
    "image_right_thumb": "data:image/jpeg;base64,...",
    "image_right_forefinger": "data:image/jpeg;base64,...",
    "image_right_middlefinger": "data:image/jpeg;base64,...",
    "image_right_thirdfinger": "data:image/jpeg;base64,...",
    "image_right_littlefinger": "data:image/jpeg;base64,..."
  }
}
```

**Caching:** 1 hour

##### POST `/api/participant/save`
Create or update participant

**Request:**
```json
{
  "participant_id": 101,        // omit for new participant
  "instance_group_id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "birthdate": "1990-01-15",
  "phone": "555-0123"
}
```

**Response:**
```json
{
  "status": "success",
  "data": { "participant_id": 101 },
  "message": "Participant saved successfully"
}
```

**Validation:**
- All fields required
- Email must be valid format
- Birthdate must be valid date

**Cache:** Invalidates participant caches

##### POST `/api/participant/delete`
Delete participant

**Request:**
```json
{
  "participant_id": 101,
  "instance_group_id": 1
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Participant deleted successfully"
}
```

**Cache:** Invalidates caches

##### POST `/api/participant/update_finger`
Upload fingerprint image

**Request:** Form-data (multipart)
```
participant_id: 101
instance_group_id: 1        // optional
hand: left                  // or 'right'
finger: thumb               // thumb|index|middle|ring|pinky
image: <binary file data>   // JPG or PNG
```

**Response:**
```json
{
  "status": "success",
  "message": "Fingerprint uploaded successfully"
}
```

**Validation:**
- File type: JPG or PNG only
- Max size: 2MB
- Hand: must be 'left' or 'right'
- Finger: must be valid finger name

**Error Response:**
```json
{
  "status": "error",
  "errors": {
    "image": "File size cannot exceed 2MB",
    "hand": "Invalid hand value"
  }
}
```

**Image Processing:**
- Converted to base64 data URI
- Field name: `image_{hand}_{fingerName}`
- Example: `image_left_thumb`, `image_right_forefinger`

**Cache:** Invalidates participant detail cache

---

## Setup & Installation

### Prerequisites

**System Requirements:**
- PHP 8.1 or higher
- PHP Extensions:
  - `intl` (required)
  - `mbstring` (required)
  - `json` (typically enabled)
  - `curl` (for HTTP requests)
  - `mysqlnd` (for MySQLi)
- Composer 2.0+
- Web server (Apache, Nginx, or use PHP built-in server)

**Priadi API Access:**
- API Key
- API Secret
- API Base URL (or use default)

### Installation Steps

#### 1. Clone/Download Project
```bash
cd /path/to/project
ls -la  # Verify project structure
```

#### 2. Copy Environment File
```bash
cp env .env
```

#### 3. Edit `.env` Configuration

```bash
# Core settings
app.baseURL = 'http://localhost:8080/'
app.forceGlobalSecureRequests = false
app.CSPEnabled = false

# Priadi API Credentials (REQUIRED)
P2F_API_KEY = your_api_key_here
P2F_API_SECRET = your_api_secret_here
P2F_API_JWT = false

# Database (optional, only if using local DB)
database.default.hostname = localhost
database.default.username = root
database.default.password = your_password
database.default.database = fingerprint_db

# Cache
cache.handler = file
cache.file.path = WRITEPATH/cache

# Session
session.driver = FileHandler
session.savePath = null

# Logger
logger.threshold = 4
```

#### 4. Install Custom Priadi Dependencies

The application requires the custom Priadi API client library. Copy it from the project's Libraries folder to the vendor directory:

```bash
# Copy the priadi folder from app/Libraries to vendor
cp -r app/Libraries/priadi vendor/

# Verify the structure is correct
ls -la vendor/priadi/priadi-api-client/src/
```

The composer.json already includes the autoload configuration for Priadi:
```json
"autoload": {
  "psr-4": {
    "Priadi\\ApiClient\\": "vendor/priadi/priadi-api-client/src"
  }
}
```

Update the autoloader cache:
```bash
composer dump-autoload
```

#### 5. Install Dependencies
```bash
composer install
```

#### 6. Create Writable Directories
```bash
chmod -R 755 writable/
chmod -R 777 writable/cache
chmod -R 777 writable/logs
chmod -R 777 writable/session
chmod -R 777 writable/uploads
```

#### 7. Run Application

**Using PHP Built-in Server:**
```bash
php spark serve --host 0.0.0.0 --port 8080
```

**Access in browser:** `http://localhost:8080`

**Using Apache/Nginx:**
- Set document root to `/public` directory
- Ensure `.htaccess` is enabled (if using Apache)
- Configure virtual host to serve `/public` as root

#### 8. Verify Installation
```bash
# Check dependencies
php spark list

# Run tests (if any)
vendor/bin/phpunit
```

### Directory Permissions

Web server must have write access to:
```
writable/
├── cache/      (644 or 755)
├── logs/       (644 or 755)
├── session/    (644 or 755)
└── uploads/    (755 or 777)
```

**For Apache/Nginx:**
```bash
sudo chown -R www-data:www-data /path/to/project/writable/
sudo chmod -R 775 /path/to/project/writable/
```

---

## Usage Guide

### Web Interface

#### 1. Homepage
**URL:** `http://localhost:8080/`

- Welcome message
- Navigation links to Groups and Participants sections

#### 2. Group Management
**URL:** `http://localhost:8080/group`

**Operations:**
- **View Groups:** DataTable displays all registered groups
- **Add Group:**
  - Click "Add Group" button → Modal opens
  - Enter group name and address
  - Click "Save"
- **Edit Group:**
  - Click "Edit" icon on row → Modal opens with current data
  - Modify fields → Click "Save"
- **Delete Group:**
  - Click "Delete" icon → Confirmation dialog
  - Confirm to remove group and all associated participants

**Columns:**
- No. (Group ID)
- Name
- Address

#### 3. Participant Management
**URL:** `http://localhost:8080/participant?instance_group_id={group_id}`

**Operations:**
- **View Participants:**
  - Select group from sidebar or use group link from Groups page
  - DataTable shows all participants in selected group
- **Add Participant:**
  - Click "Add Participant" → Modal opens
  - Fill form:
    - Name (required)
    - Email (required, valid format)
    - Birthdate (required)
    - Phone (required)
  - Click "Save"
- **Edit Participant:**
  - Click "Edit" → Modal with current data
  - Modify fields → "Save"
- **Delete Participant:**
  - Click "Delete" → Confirmation
  - Confirm to remove

**Columns:**
- No. (Participant ID)
- Name
- Birthdate
- Email
- Phone
- Actions (Edit, Delete, View)

#### 4. Fingerprint Capture
**URL:** `http://localhost:8080/participant/{participant_id}`

**Interface:**
- **Participant Information** - Displays participant details
- **Fingerprint Grid** - 10 image placeholders (5 left + 5 right)
  - Layout: Left hand (top row), Right hand (bottom row)
  - Each finger: Thumb, Index, Middle, Ring, Pinky

**Capturing Fingerprint:**
1. Click on any fingerprint placeholder image
2. File dialog opens → Select JPG or PNG image (max 2MB)
3. Image preview loads
4. Success badge (✓) shows when upload completes
5. Image displays with checkmark overlay

**Supported Formats:**
- JPEG (.jpg, .jpeg)
- PNG (.png)
- Max file size: 2MB

**Finger Order:**
```
Left Hand:  Thumb | Index | Middle | Ring | Pinky
Right Hand: Thumb | Index | Middle | Ring | Pinky
```

---

### API Usage Examples

#### Using cURL

**Get All Groups:**
```bash
curl -X GET http://localhost:8080/api/group \
  -H "Content-Type: application/json"
```

**Create Group:**
```bash
curl -X POST http://localhost:8080/api/group/save \
  -H "Content-Type: application/json" \
  -d '{
    "instance_group_name": "Police Department",
    "address": "123 Police Plaza"
  }'
```

**Get Participants in Group:**
```bash
curl -X GET "http://localhost:8080/api/participant?instance_group_id=1" \
  -H "Content-Type: application/json"
```

**Create Participant:**
```bash
curl -X POST http://localhost:8080/api/participant/save \
  -H "Content-Type: application/json" \
  -d '{
    "instance_group_id": 1,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "birthdate": "1985-06-20",
    "phone": "555-9876"
  }'
```

**Upload Fingerprint:**
```bash
curl -X POST http://localhost:8080/api/participant/update_finger \
  -F "participant_id=101" \
  -F "instance_group_id=1" \
  -F "hand=left" \
  -F "finger=thumb" \
  -F "image=@/path/to/fingerprint.jpg"
```

#### Using JavaScript/Axios

**Get All Groups:**
```javascript
axios.get('/api/group')
  .then(response => {
    console.log('Groups:', response.data.data);
  })
  .catch(error => {
    console.error('Error:', error.response.data.message);
  });
```

**Create Participant:**
```javascript
const participantData = {
  instance_group_id: 1,
  name: 'John Doe',
  email: 'john@example.com',
  birthdate: '1990-01-15',
  phone: '555-0123'
};

axios.post('/api/participant/save', participantData)
  .then(response => {
    console.log('Participant created:', response.data.data);
  })
  .catch(error => {
    console.error('Validation errors:', error.response.data.errors);
  });
```

**Upload Fingerprint:**
```javascript
const formData = new FormData();
formData.append('participant_id', 101);
formData.append('instance_group_id', 1);
formData.append('hand', 'left');
formData.append('finger', 'thumb');
formData.append('image', imageFile); // File object from input

axios.post('/api/participant/update_finger', formData)
  .then(response => {
    console.log('Upload successful:', response.data.message);
  })
  .catch(error => {
    console.error('Upload failed:', error.response.data.errors);
  });
```

---

## Best Practices

### Development Guidelines

#### 1. Environment Variables
- **Never** commit `.env` file to version control
- Always use `.env` for sensitive credentials
- Never hardcode API keys, secrets, or sensitive data

```bash
# Good
P2F_API_KEY = env('API_KEY')

# Bad
$key = 'actual-api-key-123';
```

#### 2. Error Handling
- Always return JSON responses with proper status and error messages
- Validate input before processing
- Log errors for debugging

```php
// Good error response
return response()->json([
    'status' => 'error',
    'errors' => [
        'email' => 'Invalid email format',
        'phone' => 'Phone number required'
    ]
], 400);
```

#### 3. Caching Strategy
- Cache API responses to reduce external calls
- Set appropriate TTL (Time-To-Live) values
- Invalidate cache when data changes

```php
// Get from cache or fetch from API
$groups = cache('instance_groups') ?? 
          $apiClient->get('instance-group');

// Save to cache with 1-hour TTL
cache()->save('instance_groups', $groups, 3600);
```

#### 4. File Upload Security
- Validate file type (not just by extension)
- Enforce file size limits
- Store uploaded files outside public web root (or in restricted directory)

```php
// Always validate before upload
if ($file->getSize() > 2 * 1024 * 1024) { // 2MB
    return error('File too large');
}

$mimeType = $file->getMimeType();
if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
    return error('Invalid file type');
}
```

#### 5. Input Validation
- Validate all user inputs on server-side
- Never trust client-side validation alone
- Use CodeIgniter's validation rules

```php
$validation = \Config\Services::validation();
$validation->setRules([
    'email' => 'required|valid_email',
    'birthdate' => 'required|valid_date',
    'phone' => 'required|numeric'
]);

if (!$validation->run($data)) {
    return error($validation->getErrors());
}
```

#### 6. API Integration
- Use the ApiClient wrapper for all external API calls
- Handle timeouts and retries gracefully
- Log API interactions for debugging

### Performance Optimization

#### 1. Caching
- Cache frequently accessed data (groups, participant lists)
- Use 1-hour TTL for data that doesn't change often
- Invalidate cache immediately after updates

#### 2. Database Queries
- Minimize number of API calls
- Use filtering parameters to reduce response size
- Implement pagination for large datasets

#### 3. Frontend
- Use DataTables for efficient table rendering
- Lazy-load images where possible
- Minify CSS/JS in production

### Security Checklist

- ✅ API credentials in `.env` file, never in code
- ✅ Input validation on all endpoints
- ✅ File upload type and size validation
- ✅ CSRF protection enabled (CodeIgniter default)
- ✅ Error messages don't expose sensitive info
- ✅ Writable directories have restricted permissions
- ⚠️ TODO: Enable HTTPS in production (set `forceGlobalSecureRequests = true`)
- ⚠️ TODO: Implement rate limiting on API endpoints
- ⚠️ TODO: Add user authentication/authorization
- ⚠️ TODO: Implement request signing for API integrity

### Common Issues & Solutions

#### Issue: "API Key not found" error
**Solution:**
- Verify `.env` contains `P2F_API_KEY`, `P2F_API_SECRET`
- Check `.env` syntax (no extra spaces around `=`)
- Restart PHP server after .env changes

#### Issue: "File upload fails"
**Solution:**
- Check file size (max 2MB)
- Verify format is JPG or PNG
- Ensure MIME type is correct

#### Issue: "Cache not clearing"
**Solution:**
- Check `/writable/cache/` directory is writable
- Clear cache manually: `rm -rf writable/cache/*`
- Verify cache TTL in code

#### Issue: "Groups/Participants not loading"
**Solution:**
- Verify API credentials in `.env`
- Check network connectivity to Priadi API
- Check server logs: `tail -f writable/logs/log-*.log`
- Test API directly with cURL

### Code Examples

#### Creating a Custom Helper Function
```php
// app/Helpers/FingerHelper.php
<?php

namespace App\Helpers;

class FingerHelper {
    
    /**
     * Get readable finger name
     * @param string $finger API finger name
     * @return string User-friendly name
     */
    public static function getFriendlyName($finger) {
        return match($finger) {
            'thumb' => 'Thumb',
            'forefinger' => 'Index',
            'middlefinger' => 'Middle',
            'thirdfinger' => 'Ring',
            'littlefinger' => 'Pinky',
            default => $finger
        };
    }
    
    /**
     * Map UI finger to API finger name
     * @param string $uiFinger User interface finger name
     * @return string API finger name
     */
    public static function toApiName($uiFinger) {
        return match($uiFinger) {
            'thumb' => 'thumb',
            'index' => 'forefinger',
            'middle' => 'middlefinger',
            'ring' => 'thirdfinger',
            'pinky' => 'littlefinger',
            default => $uiFinger
        };
    }
}
?>
```

#### Custom Validation Rule
```php
// In ApiController::saveParticipant()
$validation = \Config\Services::validation();
$validation->setRules([
    'instance_group_id' => 'required|integer',
    'name' => 'required|min_length[3]',
    'email' => 'required|valid_email',
    'birthdate' => 'required|valid_date[Y-m-d]',
    'phone' => 'required|regex_match[/^\d{10,15}$/]'
]);

if (!$validation->run($data)) {
    return response()->json([
        'status' => 'error',
        'errors' => $validation->getErrors()
    ], 400);
}
```

#### Error Handling in API calls
```php
try {
    $response = $this->apiClient->get('instance-group');
    
    if ($response['status'] === 'error') {
        throw new \Exception($response['message']);
    }
    
    return $response['data'];
    
} catch (\GuzzleHttp\Exception\ConnectException $e) {
    log_message('error', 'API Connection Error: ' . $e->getMessage());
    return error('Unable to connect to API');
} catch (\Exception $e) {
    log_message('error', 'API Error: ' . $e->getMessage());
    return error($e->getMessage());
}
```

### Improvement Suggestions

1. **Authentication & Authorization**
   - Add user login system
   - Implement role-based access control (admin, officer, viewer)
   - Restrict operations based on user role

2. **API Rate Limiting**
   - Implement rate limiting to prevent abuse
   - Use middleware or service to enforce limits

3. **Fingerprint Quality Validation**
   - Add image quality checks (brightness, contrast)
   - Validate fingerprint clarity before accepting upload

4. **Biometric Matching**
   - Integrate fingerprint matching algorithm
   - Implement duplicate detection

5. **Advanced Caching**
   - Implement Redis for distributed caching
   - Use query result caching

6. **Audit Logging**
   - Log all create/update/delete operations
   - Track who/when/what of data changes

7. **Mobile Support**
   - Develop mobile app for on-field fingerprint capture
   - Enable offline mode with sync capability

8. **Export Features**
   - Export participant data as CSV/PDF
   - Generate fingerprint reports

9. **Data Encryption**
   - Encrypt sensitive participant data at rest
   - Use SSL/TLS for all API communication

10. **Testing**
    - Expand unit test coverage
    - Add API integration tests
    - Create E2E tests for critical workflows

---

## Troubleshooting

### Installation Issues

**Problem:** Composer install fails
```bash
# Solution: Update composer and try again
composer self-update
composer install --no-dev
```

**Problem:** Permission denied on writable directory
```bash
# Solution: Fix permissions
sudo chown -R $USER:$USER writable/
chmod -R 755 writable/
chmod -R 755 writable/cache writable/logs writable/session
```

### Runtime Issues

**Problem:** "Call to undefined method" error
```bash
# Solution: Verify autoloading
composer dumpautoload -o
```

**Problem:** API requests timeout
```bash
# Solution: Increase timeout, check network
# In app/Controllers/ApiController.php config
// Increase Guzzle timeout
'timeout' => 30 // seconds
```

**Problem:** Session not working
```bash
# Solution: Ensure session path is writable
chmod 777 writable/session/
```

---

## Support & Resources

- **CodeIgniter 4 Documentation:** https://codeigniter.com/user_guide/
- **Priadi API Documentation:** (provided by API vendor)
- **Bootstrap Documentation:** https://getbootstrap.com/docs/
- **DataTables Documentation:** https://datatables.net/

---

## License

See [LICENSE](LICENSE) file for details.

---

**Last Updated:** March 2026  
**Version:** 1.0.0  
**Maintainer:** [Your Team/Organization]
