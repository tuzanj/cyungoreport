# CyungoReport System - New Features Implementation

## Overview
This document outlines all the new features that have been implemented in the CyungoReport academic management system to support:
- Learner Assessment Reports with behavior tracking
- PDF and Excel export functionality
- Bulk student registration with Excel import templates
- Trade-based organization (Rwanda TVET Board - RTB terminology)
- Enhanced user role management

---

## 1. Database Schema Updates

### New Tables & Modifications

#### 1.1 Behavior Tracking
**New Table:** `behavior_records`
- Tracks student conduct and behavior grades
- Fields: student_id, class_course_id, term, academic_year_id, behavior_grade, conduct_score, remarks, recorded_by
- Allows recording of behavioral assessments per course per term

#### 1.2 Updated Marks Table
- Added `behavior_grade` field for per-course behavior assessment
- Added `behavior_remarks` field for teacher comments on behavior

#### 1.3 Department to Trade Conversion
- Renamed `departments` table to `trades`
- Updated foreign keys in:
  - `courses.department_id` → `courses.trade_id`
  - `teachers.department_id` → `teachers.trade_id`
- This aligns with Rwanda TVET Board (RTB) terminology

#### 1.4 File Uploads Table
**New Table:** `file_uploads`
- Tracks bulk import files
- Fields: user_id, file_name, file_path, upload_type, status, error_message
- Supports audit trail for data imports

#### 1.5 User Role Extension
- Added `discipline_master` role to the roles ENUM in `users` table
- Allows creation of dedicated discipline/conduct officers

---

## 2. Learner Assessment Report

### 2.1 Report Features
- **Student Information**: Name, ID, class, academic year, DOB, gender
- **Academic Performance**: All courses with:
  - 1st Term, 2nd Term, 3rd Term scores
  - Annual percentage and letter grade
  - Pass/Fail status
  - Individual course remarks
- **Behavior Assessment**:
  - Overall conduct grade
  - Teacher remarks on character/conduct
  - Formatted based on the RTB standard report template

### 2.2 Report Formats

#### PDF Reports
- Professional, printable format
- Includes school header with branding
- Color-coded tables for readability
- Signature section for teacher and school manager
- Uses DOMPDF library (optional - requires composer installation)
- Fallback to HTML for browser printing if DOMPDF not available

#### Excel Reports
- Structured spreadsheet format for record-keeping
- Color-coded cells (green for pass, red for fail)
- Formatted headers and data rows
- Uses PhpSpreadsheet library (optional)
- Fallback to CSV for compatibility

### 2.3 Generating Reports
**Admin Panel Access:** `/admin/reports.php`

Steps:
1. Select Academic Year
2. Select Student
3. Choose Export Format (PDF or Excel)
4. Click "Generate Report"

The system will:
- Fetch all student marks for the academic year
- Compile behavior records for the student
- Format data according to selected report type
- Automatically trigger download

---

## 3. Bulk Student Registration

### 3.1 Features
- **Excel Template Import**: Register multiple students at once
- **CSV/XLS/XLSX Support**: Multiple file format compatibility
- **Validation**: Checks for required fields and data integrity
- **Error Handling**: Detailed error messages for failed rows
- **Automatic Enrollment**: Can automatically assign to classes

### 3.2 CSV Template Format
```csv
first_name,last_name,gender,date_of_birth,phone,address,emergency_contact,class_id
John,Doe,male,2006-05-15,0788123456,123 Main St,Jane Doe (Mother),1
Jane,Smith,female,2006-08-20,0789654321,456 Oak Ave,John Smith (Father),1
```

**Required Columns:**
- `first_name` - Student's first name
- `last_name` - Student's last name

**Optional Columns:**
- `gender` - male/female/other
- `date_of_birth` - Format: YYYY-MM-DD
- `phone` - Phone number
- `address` - Physical address
- `emergency_contact` - Emergency contact person
- `class_id` - Class ID for automatic enrollment

### 3.3 Using Bulk Import
**Admin Panel Access:** `/admin/students.php?action=import`

Steps:
1. Download the CSV template
2. Fill in student data in Excel/Spreadsheet
3. Save as CSV format
4. Upload the file
5. System will:
   - Create user accounts for each student
   - Generate Student IDs
   - Assign to classes (if class_id provided)
   - Send account credentials to notification system
   - Log all actions in audit trail

---

## 4. User Role Management

### 4.1 Available Roles
1. **Admin** - Full system access, user management, configuration
2. **Secretary** - Student enrollment, credentials, records
3. **Teacher** - Marks entry, attendance, course management
4. **Student** - Profile, marks, schedule, transcript access
5. **Parent** - Child performance, messages, notifications
6. **Discipline Master** - NEW - Behavior/conduct management

### 4.2 Discipline Master Role
**Purpose:** Monitor and record student behavior/conduct
**Capabilities:**
- Record behavior grades for students
- Track conduct scores per course per term
- Generate behavior reports
- Communicate with teachers/parents about conduct issues

### 4.3 User Management Interface
**Admin Panel Access:** `/admin/users.php`

**Features:**
- View all system users
- Update user roles
- Activate/deactivate accounts
- Reset user passwords
- Create new Discipline Master accounts
- Search and filter users

**How to Update User Role:**
1. Go to User Management page
2. Click the role icon (👤) on any user
3. Select new role from dropdown
4. Confirm update
5. User role is immediately changed and logged

**How to Create Discipline Master:**
1. Click "Discipline Master" button
2. Enter username and email
3. System generates temporary password
4. Account is created and notification is sent

---

## 5. Student Registration Admin Panel

### 5.1 Manual Registration
**Access:** `/admin/students.php?action=register`

**Form Fields:**
- First Name (required)
- Last Name (required)
- Username (required, unique)
- Email (required, unique)
- Gender
- Date of Birth
- Phone
- Address
- Emergency Contact
- Class Assignment

**Process:**
- Creates user account with temporary password
- Generates unique Student ID (YYYY####)
- Creates student record with all details
- Optionally enrolls in selected class
- Sends credentials notification

### 5.2 Student Listing
**Access:** `/admin/students.php`

Shows all registered students with:
- Student ID
- Full Name
- Username/Email
- Assigned Class
- Account Status
- Quick links to view profiles

---

## 6. Trade Management (RTB)

### 6.1 Purpose
Organize teachers and courses by trade (profession/specialization) following Rwanda TVET Board standards.

### 6.2 Trade Features
**Access:** `/admin/departments.php`

**Can:**
- Create new trades
- View all trades with statistics
- See count of teachers per trade
- See count of courses per trade
- Delete unused trades

**Example Trades:**
- Automotive Technology
- Welding & Fabrication
- Electrical Installation
- Plumbing & Gas Fitting
- Construction
- Information Technology

---

## 7. Models & Controllers

### 7.1 New Models

#### ReportModel
```php
$reportModel = new ReportModel();

// Get comprehensive student report data
$data = $reportModel->getStudentTermReport($studentId, $academicYearId);

// Get class-level report
$classData = $reportModel->getClassReport($classId, $academicYearId);

// Calculate statistics
$stats = $reportModel->calculateMarkStatistics($marks);
```

#### BehaviorModel
```php
$behaviorModel = new BehaviorModel();

// Record behavior
$id = $behaviorModel->recordBehavior(
    $studentId, $courseCourseId, $term, 
    $academicYearId, 
    ['behavior_grade' => 'A', 'conduct_score' => 95, 'remarks' => '...']
);

// Get student behavior records
$behaviors = $behaviorModel->getStudentBehavior($studentId, $academicYearId);

// Get overall term behavior grade
$termGrade = $behaviorModel->getTermBehaviorGrade($studentId, $academicYearId, $term);
```

### 7.2 Updated Models

#### CourseModel
- Changed `getAllWithDept()` to `getAllWithTrade()`
- Updated queries to use `trade_id` instead of `department_id`

#### TeacherModel
- Changed `getAllWithDept()` to `getAllWithTrade()`
- Updated insert/update queries to use `trade_id`

#### StudentModel
- Supporting bulk operations
- Student ID generation

### 7.3 AdminController Enhancements

**New Methods:**
```php
// Student Registration
$admin->createStudent($data);
$admin->importStudentsFromCSV($filePath);
$admin->getStudentImportTemplate();
$admin->updateStudent($studentId, $data);

// User Management
$admin->getAllUsers();
$admin->updateUserRole($userId, $newRole);
$admin->createDisciplineMaster($data);

// Report Generation
$admin->generateStudentReportPDF($studentId, $academicYearId, 'pdf');
$admin->generateStudentReportPDF($studentId, $academicYearId, 'excel');
```

---

## 8. Helper Classes

### 8.1 ExcelStudentImporter
```php
$importer = new ExcelStudentImporter();
$result = $importer->importFromCSV($filePath);

// Returns:
// ['success' => true, 'imported' => 5, 'failed' => 2, 'errors' => []]
```

### 8.2 PDFReportGenerator
```php
$generator = new PDFReportGenerator($reportData, $fileName);
$generator->generate(); // Outputs PDF or HTML
```

### 8.3 ExcelReportGenerator
```php
$generator = new ExcelReportGenerator($reportData, $fileName);
$generator->generate(); // Outputs XLSX or CSV
```

---

## 9. File Structure

```
admin/
  ├── students.php       [NEW/UPDATED] - Student management, registration, bulk import
  ├── users.php          [UPDATED] - User role and discipline master management
  ├── reports.php        [NEW] - Report generation interface
  └── departments.php    [UPDATED] - Now "Trades" (RTB)

classes/
  ├── ReportGenerator.php [NEW] - Base class for reports
  ├── PDFReportGenerator.php [NEW] - PDF generation
  ├── ExcelReportGenerator.php [NEW] - Excel/CSV generation
  └── ExcelStudentImporter.php [NEW] - Bulk student import

database/
  └── migrations/
      └── 001_add_behavior_and_trade.sql [NEW] - Schema updates

models/
  ├── ReportModel.php    [NEW] - Report data queries
  ├── BehaviorModel.php  [NEW] - Behavior tracking
  ├── CourseModel.php    [UPDATED] - trade_id support
  ├── TeacherModel.php   [UPDATED] - trade_id support
  └── StudentModel.php   [UPDATED] - Enhanced for bulk operations

controllers/
  └── AdminController.php [UPDATED] - New methods for reports, students, users, roles
```

---

## 10. Installation & Setup

### 10.1 Database Migration
Run the migration script to update your database:

```bash
mysql -u username -p database_name < database/migrations/001_add_behavior_and_trade.sql
```

Or manually execute the SQL in your database client.

### 10.2 Optional: Report Libraries

For better PDF/Excel support, install via composer:

```bash
composer require dompdf/dompdf
composer require phpoffice/phpspreadsheet
```

If not installed, the system will fall back to HTML (PDF) and CSV (Excel) formats.

### 10.3 File Permissions
Ensure the system can write to:
- `/uploads/imports/` - For student import files
- `/uploads/reports/` - For cached reports (optional)

```bash
mkdir -p uploads/{imports,reports}
chmod 755 uploads/{imports,reports}
```

---

## 11. Usage Workflow

### 11.1 Start-of-Year Setup
1. Create academic year
2. Define trades (departments)
3. Create courses and assign to trades
4. Create classes
5. Register teachers and assign to trades
6. Bulk import students or register individually
7. Enroll students in classes
8. Assign courses to classes with teachers

### 11.2 During Academic Year
1. Teachers enter marks (assignments, quizzes, midterm, final)
2. Teachers record behavior grades
3. Discipline Master monitors conduct issues
4. Admin generates periodic reports for review

### 11.3 End-of-Term/Year Reporting
1. Admin generates PDF/Excel reports for students
2. Schools can print reports for distribution
3. Records are archived for audit trail
4. Data is ready for REB (Rwanda Education Board) compliance

---

## 12. RTB/REB Compliance

The system now supports:
- **RTB (Rwanda TVET Board)** - Trade-based organization and reporting
- **REB (Rwanda Education Board)** - Standard academic reporting

Reports include:
- Behavior assessment (character grades)
- Course-by-course performance
- Term grouping (1st, 2nd, 3rd term)
- Overall annual performance
- Teacher and school manager signatures

---

## 13. Troubleshooting

### Report Generation Issues
- **"Student not found"**: Verify student exists and has marks recorded
- **PDF not generating**: Ensure DOMPDF is installed or use HTML export
- **Excel not generating**: Ensure PhpSpreadsheet is installed or use CSV export

### Student Import Issues
- **File upload fails**: Check file permissions on `/uploads/` directory
- **"Invalid format"**: Ensure CSV headers match template exactly
- **Validation errors**: Check date format (YYYY-MM-DD) and gender values (male/female/other)

### User Management Issues
- **Can't update role**: Verify user has admin permissions
- **Discipline Master not created**: Check username/email are unique

---

## 14. Future Enhancements

Potential improvements:
- Batch report generation for entire classes
- Report scheduling and automated distribution
- Behavior trend analysis
- Custom report templates
- Multi-language report support
- TPAD (Teacher Performance Appraisal Documents) integration
- Parent portal enhancements with behavior tracking

---

## 15. Support & Maintenance

For issues or feature requests:
1. Check audit logs for error details
2. Review database migration logs
3. Test with sample data first
4. Contact system administrator for database issues

---

**Last Updated:** May 2026
**System Version:** 2.0
**RTB Compliance:** Yes
