Project Structure 

app/
 ├── Actions/
 │    ├── Users/
 │    │     ├── CreateUserAction.php
 │    │     ├── ShowUserAction.php
 │    │     ├── UpdateUserAction.php
 │    │     └── DeleteUserAction.php
 │    ├── Courses/
 │    │     ├── CreateCourseAction.php
 │    │     ├── ShowCourseAction.php
 │    │     ├── UpdateCourseAction.php
 │    │     └── DeleteCourseAction.php
 │    ├── Bootcamps/
 │    ├── Enrollments/
 │    ├── Assignments/
 │    ├── Submissions/
 │    ├── Notifications/
 │    └── Reports/
 │
 ├── Repositories/
 │    ├── Interfaces/
 │    │     ├── UserRepositoryInterface.php
 │    │     ├── CourseRepositoryInterface.php
 │    │     ├── BootcampRepositoryInterface.php
 │    │     ├── EnrollmentRepositoryInterface.php
 │    │     ├── AssignmentRepositoryInterface.php
 │    │     ├── SubmissionRepositoryInterface.php
 │    │     ├── NotificationRepositoryInterface.php
 │    │     └── ReportRepositoryInterface.php
 │    │
 │    ├── User/
 │    │     └── UserRepository.php
 │    ├── Course/
 │    │     └── CourseRepository.php
 │    ├── Bootcamp/
 │    │     └── BootcampRepository.php
 │    ├── Enrollment/
 │    │     └── EnrollmentRepository.php
 │    ├── Assignment/
 │    │     └── AssignmentRepository.php
 │    ├── Submission/
 │    │     └── SubmissionRepository.php
 │    ├── Notification/
 │    │     └── NotificationRepository.php
 │    └── Report/
 │          └── ReportRepository.php
 │
 └── Http/
      └── Controllers/
           └── Api/
                ├── UserController/
                ├── CourseController/
                ├── BootcampController/
                ├── EnrollmentController/
                ├── AssignmentController/
                ├── SubmissionController/
                ├── NotificationController/
                └── ReportController/





Notifications : 
🧩 Logic Summary

Admin can:

Send a notification to all users or to specific users (via user_id array)

Instructor can:

Send to their own students (linked via enrollments in their courses or bootcamps)

Student can:

Only view, mark as read/unread, and delete their own notifications

All routes are protected by auth:sanctum.


Notes about CreateNotificationAction implementation:

For bulk operations we directly insert into DB with \DB::table('notifications')->insert(...) to avoid creating huge Eloquent objects. This preserves speed for "admin -> all" or large course audiences.

The action returns a lightweight summary (count / message) for bulk sends. If you want objects returned, we can change to inserting and then querying the created records (costly for large sets).
















# Testing System API Documentation

## Authentication
All endpoints require authentication using Laravel Sanctum. Include the bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Test Endpoints

### 1. Get All Available Tests
**GET** `/api/tests`

Get all tests available to the authenticated user.

**Query Parameters:**
- `testable_type` (optional): Filter by type (`bootcamp`, `workshop`, `program`, `course`)
- `testable_id` (optional): Filter by specific entity ID

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Introduction to Programming Quiz",
      "description": "Test your basic programming knowledge",
      "testable_type": "Course",
      "testable": {
        "id": 5,
        "title": "Programming 101"
      },
      "questions_count": 10,
      "total_points": 50,
      "duration_minutes": 30,
      "passing_score": 70.00,
      "max_attempts": 3,
      "available_from": "2024-01-01T00:00:00Z",
      "available_until": "2024-12-31T23:59:59Z",
      "user_attempts": 1,
      "can_attempt": true,
      "is_available": true
    }
  ]
}
```

---

### 2. Get Tests by Entity

**GET** `/api/tests/bootcamps/{id}` - Get tests for a bootcamp  
**GET** `/api/tests/workshops/{id}` - Get tests for a workshop  
**GET** `/api/tests/programs/{id}` - Get tests for a program  
**GET** `/api/tests/courses/{id}` - Get tests for a course

**Response:** Same as "Get All Available Tests"

---

### 3. Get Specific Test Details
**GET** `/api/tests/{id}`

Get detailed information about a specific test (without revealing correct answers).

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Introduction to Programming Quiz",
    "description": "Test your basic programming knowledge",
    "testable_type": "Course",
    "testable": {
      "id": 5,
      "title": "Programming 101"
    },
    "duration_minutes": 30,
    "passing_score": 70.00,
    "max_attempts": 3,
    "shuffle_questions": true,
    "show_correct_answers": true,
    "show_results_immediately": true,
    "total_points": 50,
    "questions_count": 10,
    "user_attempts": 1,
    "can_attempt": true,
    "questions_preview": [
      {
        "type": "Multiple Choice",
        "points": 5
      },
      {
        "type": "True/False",
        "points": 2
      }
    ]
  }
}
```

**Error Responses:**
- `403` - Test not available or max attempts reached

---

### 4. Get Attempt History
**GET** `/api/tests/{testId}/history`

Get user's attempt history for a specific test.

**Response:**
```json
{
  "success": true,
  "data": {
    "test_title": "Introduction to Programming Quiz",
    "max_attempts": 3,
    "attempts_used": 2,
    "can_attempt_again": true,
    "attempts": [
      {
        "id": 10,
        "attempt_number": 2,
        "status": "graded",
        "score": 42.50,
        "total_points": 50.00,
        "percentage": 85.00,
        "passed": true,
        "started_at": "2024-01-15T10:00:00Z",
        "submitted_at": "2024-01-15T10:25:00Z",
        "graded_at": "2024-01-15T10:26:00Z"
      }
    ]
  }
}
```

---

## Test Attempt Endpoints

### 5. Start New Attempt
**POST** `/api/test-attempts/{testId}/start`

Start a new test attempt. Returns the test with questions.

**Response:**
```json
{
  "success": true,
  "message": "Test attempt started successfully.",
  "data": {
    "attempt_id": 15,
    "test": {
      "id": 1,
      "title": "Introduction to Programming Quiz",
      "description": "Test your basic programming knowledge",
      "duration_minutes": 30,
      "total_points": 50,
      "passing_score": 70.00
    },
    "questions": [
      {
        "id": 1,
        "type": "mcq",
        "question_text": "What is a variable?",
        "points": 5,
        "is_required": true,
        "options": [
          {
            "id": 1,
            "option_text": "A storage location"
          },
          {
            "id": 2,
            "option_text": "A function"
          },
          {
            "id": 3,
            "option_text": "A loop"
          }
        ]
      },
      {
        "id": 2,
        "type": "true_false",
        "question_text": "Python is a compiled language",
        "points": 2,
        "is_required": true,
        "options": [
          {
            "id": 4,
            "option_text": "True"
          },
          {
            "id": 5,
            "option_text": "False"
          }
        ]
      },
      {
        "id": 3,
        "type": "written",
        "question_text": "Explain the concept of object-oriented programming",
        "points": 10,
        "is_required": true,
        "options": null
      }
    ],
    "started_at": "2024-01-15T10:00:00Z",
    "expires_at": "2024-01-15T10:30:00Z"
  }
}
```

**Error Responses:**
- `403` - Test not available or max attempts reached

---

### 6. Get Active Attempt
**GET** `/api/test-attempts/{attemptId}`

Retrieve an in-progress attempt with current answers.

**Response:**
```json
{
  "success": true,
  "data": {
    "attempt_id": 15,
    "status": "in_progress",
    "test": {
      "id": 1,
      "title": "Introduction to Programming Quiz",
      "duration_minutes": 30
    },
    "questions": [
      {
        "id": 1,
        "type": "mcq",
        "question_text": "What is a variable?",
        "points": 5,
        "is_required": true,
        "options": [...],
        "current_answer": {
          "selected_option_id": 1,
          "written_answer": null
        }
      }
    ],
    "started_at": "2024-01-15T10:00:00Z",
    "remaining_time_seconds": 1200
  }
}
```

---

### 7. Save Answer
**POST** `/api/test-attempts/{attemptId}/answer`

Save an answer for a specific question. Can be called multiple times to update.

**Request Body:**
```json
{
  "question_id": 1,
  "selected_option_id": 2,  // For MCQ and True/False
  "written_answer": "..."    // For written questions
}
```

**Response:**
```json
{
  "success": true,
  "message": "Answer saved successfully.",
  "data": {
    "answer_id": 25,
    "question_id": 1
  }
}
```

**Error Responses:**
- `400` - Attempt not in progress or expired
- `422` - Validation errors

---

### 8. Submit Test
**POST** `/api/test-attempts/{attemptId}/submit`

Submit the test for grading. MCQ and True/False are auto-graded immediately.

**Response (Auto-graded):**
```json
{
  "success": true,
  "message": "Test submitted and graded successfully.",
  "data": {
    "attempt_id": 15,
    "status": "graded",
    "submitted_at": "2024-01-15T10:25:00Z",
    "score": 42.50,
    "total_points": 50.00,
    "percentage": 85.00,
    "passed": true,
    "passing_score": 70.00
  }
}
```

**Response (Needs Manual Grading):**
```json
{
  "success": true,
  "message": "Test submitted successfully. Waiting for manual grading.",
  "data": {
    "attempt_id": 15,
    "status": "submitted",
    "submitted_at": "2024-01-15T10:25:00Z"
  }
}
```

**Note:** If test has expired while in progress, it will be auto-submitted.

---

### 9. Get Test Result
**GET** `/api/test-attempts/{attemptId}/result`

Get the results of a submitted/graded attempt.

**Response (Graded):**
```json
{
  "success": true,
  "data": {
    "attempt_id": 15,
    "attempt_number": 2,
    "status": "graded",
    "test": {
      "id": 1,
      "title": "Introduction to Programming Quiz"
    },
    "submitted_at": "2024-01-15T10:25:00Z",
    "graded_at": "2024-01-15T10:26:00Z",
    "score": 42.50,
    "total_points": 50.00,
    "percentage": 85.00,
    "passed": true,
    "passing_score": 70.00,
    "answers": [
      {
        "question_id": 1,
        "question_text": "What is a variable?",
        "type": "mcq",
        "points_possible": 5,
        "points_earned": 5,
        "is_correct": true,
        "your_answer": "A storage location",
        "correct_answer": "A storage location",
        "explanation": "Variables are used to store data..."
      },
      {
        "question_id": 3,
        "question_text": "Explain OOP",
        "type": "written",
        "points_possible": 10,
        "points_earned": 8,
        "is_correct": false,
        "your_answer": "OOP is a programming paradigm...",
        "feedback": "Good explanation, but you missed discussing inheritance."
      }
    ]
  }
}
```

**Response (Not Yet Graded):**
```json
{
  "success": true,
  "data": {
    "attempt_id": 15,
    "attempt_number": 2,
    "status": "submitted",
    "test": {
      "id": 1,
      "title": "Introduction to Programming Quiz"
    },
    "submitted_at": "2024-01-15T10:25:00Z",
    "graded_at": null,
    "message": "Your test is awaiting manual grading by the instructor."
  }
}
```

**Error Responses:**
- `400` - Attempt not submitted yet

---

### 10. Get All User Attempts
**GET** `/api/test-attempts`

Get all attempts by the authenticated user across all tests.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "test_id": 1,
      "test_title": "Introduction to Programming Quiz",
      "attempt_number": 2,
      "status": "graded",
      "score": 42.50,
      "total_points": 50.00,
      "percentage": 85.00,
      "passed": true,
      "started_at": "2024-01-15T10:00:00Z",
      "submitted_at": "2024-01-15T10:25:00Z",
      "graded_at": "2024-01-15T10:26:00Z"
    }
  ]
}
```

---

## Error Responses

### Standard Error Format
```json
{
  "success": false,
  "message": "Error description"
}
```

### Common HTTP Status Codes
- `200` - Success
- `400` - Bad Request (invalid state)
- `401` - Unauthorized (not authenticated)
- `403` - Forbidden (no permission)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## Typical Frontend Flow

### 1. Browse Available Tests
```javascript
// Get all tests
GET /api/tests

// Or get tests for specific course
GET /api/tests/courses/5
```

### 2. View Test Details
```javascript
// Get test info before starting
GET /api/tests/1

// Check attempt history
GET /api/tests/1/history
```

### 3. Take the Test
```javascript
// Start attempt
POST /api/test-attempts/1/start
// Returns: attempt_id, questions

// Save answers as user progresses
POST /api/test-attempts/15/answer
Body: { question_id: 1, selected_option_id: 2 }

POST /api/test-attempts/15/answer
Body: { question_id: 3, written_answer: "..." }

// Submit when done
POST /api/test-attempts/15/submit
```

### 4. View Results
```javascript
// Get results
GET /api/test-attempts/15/result
```

---

## Notes

### Auto-Grading
- MCQ and True/False questions are graded immediately upon submission
- Written answers require manual grading by instructors
- If all questions are auto-gradable, results are available immediately

### Time Limits
- If a test has `duration_minutes`, the attempt expires after that time
- Expired attempts are auto-submitted when accessed
- Frontend should track remaining time using `remaining_time_seconds`

### Answer Saving
- Answers can be saved multiple times (updates existing answer)
- It's recommended to auto-save answers periodically
- Answers are preserved if the user refreshes the page

### Test Availability
- Tests must be active (`is_active = true`)
- Must be within availability window (`available_from` to `available_until`)
- User must not have exceeded `max_attempts`

### Security
- Questions are returned without correct answers during the test
- Correct answers are only revealed after submission (if `show_correct_answers = true`)
- All requests require authentication
- Users can only access their own attempts