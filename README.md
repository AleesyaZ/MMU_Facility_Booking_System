# MMU_Facility_Booking_System

![System Status](https://img.shields.io/badge/Status-Completed-success)
![Tech Stack](https://img.shields.io/badge/Tech_Stack-PHP_|_MySQL_|_TailwindCSS-blue)
![Version](https://img.shields.io/badge/Version-1.0.0-informational)

## Overview
**MMU Facility Booking System** is a web-based facility management and reservation platform designed specifically for Multimedia University (Cyberjaya & Melaka campuses). 

Developed to replace outdated physical QR-code systems and manual logging, this system provides a centralized, automated hub for Students, Lecturers, and Administrators. It emphasizes **Fair Access** through automated weekly quotas, promotes **Accountability** via a penalty-strike system, and reduces staff dependency through self-service dashboards and automated email notifications.

## Key Features

### For Students
* **Automated Account Activation:** Secure login using official MMU emails and One-Time Password (OTP) verification via PHPMailer. No open public registrations.
* **Fair-Use Quota Engine:** Enforces a strict 2-bookings-per-week limit to prevent facility monopolization.
* **Visual Timetable:** Interactive weekly calendars showing available, booked, and fixed-academic slots.
* **Add-On Equipment:** Dynamically request inventory (e.g., projectors, sports nets) during room reservation.
* **Activity Feed Issue Reporting:** Report facility damage or room misuse (with drag-and-drop photo evidence) in a modern, scrolling UI feed featuring direct Admin replies.

### For Lecturers
* **Priority Overrides:** Ability to bypass standard quotas for official academic purposes (e.g., replacement classes).
* **Document Verification:** Upload official faculty memos or timetables as proof when requesting a priority override.

### For Administrators
* **Advanced Dashboard:** Metrics tracking pending overrides, open issue reports, and daily campus usage.
* **Penalty & Strike System:** Issue manual strikes for no-shows or vandalism. Accumulating 3 strikes results in an automatic 30-day user account suspension.
* **Inventory Management:** Visual progress bars to track total vs. available equipment stock across campuses.
* **Schedule Editor:** Block out "Fixed Academic Classes" on facility timetables with customizable recurrence (e.g., 7-week or 14-week semesters).
* **Broadcast Announcements:** Publish campus updates that displays directly on the student and lecturer dashboards.

## Tech Stack
* **Frontend:** HTML5, CSS3, JavaScript (Vanilla), TailwindCSS.
* **Backend:** Core PHP (8.x).
* **Database:** MySQL / MariaDB.
* **Integrations:** PHPMailer (SMTP for OTP and automated alerts).
* **Architecture:** 3-Tier Architecture with Role-Based Access Control (RBAC).

## Installation & Local Setup

**1. Environment Setup**
* Download and install [XAMPP](https://www.apachefriends.org/) (PHP 8.2+ recommended).
* Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.

**2. Database Configuration**
* Navigate to `http://localhost/phpmyadmin`.
* Create a new database named `online_booking_system`.
* Import the provided `online_booking_system.sql` file.

**3. Source Code Deployment**
* Clone this repository into your XAMPP `htdocs` directory:
  ```bash
  https://github.com/AleesyaZ/MMU_Facility_Booking_System.git

**4. Configuration**
* Open PHP/db_config.php and verify database credentials (root / "").
* For the OTP email functionality to work, ensure the SMTP credentials in the PHPMailer configuration files are updated with a valid Gmail App Password.
Ensure the public/uploads/ directory has write permissions.

**5. Launch**
Open your browser and navigate to: http://localhost/booking_system/prototypes/index.php

## How to Test the System (Account Creation Guide)

Because this system simulates a closed university environment, public registration is disabled. To test the system, users must first be "pre-registered" in the database by an administrator. 

To test the real-time OTP Activation feature, please follow these steps:

### Step 1: Pre-Register Your Email (Simulating Admin Action)
1. Open `http://localhost/phpmyadmin` and navigate to the `online_booking_system` database.
2. Open the `user` table and click **Insert**.
3. Fill in the following fields:
   *   **name:** Enter your name.
   *   **email:** Enter a **real, accessible email address** (required to receive the OTP).
   *   **role:** Type `Student`, `Lecturer`, or `Admin` (depending on which interface you wish to test).
   *   **is_activated:** Leave as `0`.
   *   *(Leave all other fields blank or as their default values).*
4. Click **Go** to save the user record.

### Step 2: Activate the Account (Simulating User Action)
1. Navigate to the system at: `http://localhost/booking_system/prototypes/activate.html` (or click **"Activate Account"** on the Landing Page).
2. Enter the exact email address you just registered in the database.
3. Click **"Get OTP"**. The system will use PHPMailer to send a 6-digit code to your real email inbox.
4. Retrieve the code, enter it into the form, and create your own secure password.
5. Click **Activate & Login** to securely access your role-specific dashboard.

*(Note: To try out the system's role-based features such as Priority Overrides and Standard Quotas, repeat the process to create both a `Student` and a `Lecturer` account).*

## Contributors
### Aleesya Zaara Binti Edi Zulkarnain - UI/UX Design, System Architecture (ERD/DFD), Documentation.
### Idris Bin Shah Nahar - Backend Development (PHP/MySQL), Database Integration, GitHub Management.
### Suriya - Project Management, System Testing, Quality Assurance.



